<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use App\Traits\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpPresentation\IOFactory;
use ZipArchive;
use DOMDocument;
use DOMXPath;

class Page_Quiz extends MainController
{
    use AuditLogger;

// ── Simple token replacement on raw slide XML ────────────────────────
private function replaceTokensInSlideXml(string $xml, array $tokens): string
{
    foreach ($tokens as $token => $value) {
        $safe = htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $xml  = str_replace(htmlspecialchars($token, ENT_XML1), $safe, $xml);
        $xml  = str_replace($token, $safe, $xml);
    }
    return $xml;
}

// ── Replace {{OPTIONS}} with one properly-styled <a:p> per option ────
private function buildOptionParagraphsInSlideXml(string $slideXml, array $options): string
{
    $optionLetters = ['A','B','C','D','E','F','G','H','I','J'];

    if (empty($options)) {
        return str_replace('{{OPTIONS}}', '', $slideXml);
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;

    if (!$dom->loadXML($slideXml)) {
        $lines = [];
        foreach ($options as $i => $opt) {
            $lines[] = ($optionLetters[$i] ?? $i + 1) . '.  ' . ($opt['text'] ?? '');
        }
        return str_replace('{{OPTIONS}}', implode(' | ', $lines), $slideXml);
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');

    // Scan every <a:p> and concatenate ALL its <a:t> text to find the placeholder.
    // This handles split-run XML where {{OPTIONS}} is spread across multiple <a:t> nodes.
    $allParas = $xpath->query('//a:p');
    $paraNode = null;

    foreach ($allParas as $para) {
        $fullText = '';
        foreach ($xpath->query('.//a:t', $para) as $t) {
            $fullText .= $t->nodeValue;
        }
        if (str_contains($fullText, '{{OPTIONS}}')) {
            $paraNode = $para;
            break;
        }
    }

    if ($paraNode === null) {
        // Placeholder genuinely not present in this slide template
        return $slideXml;
    }

    $bodyNode = $paraNode->parentNode;

    foreach ($options as $i => $opt) {
        $label   = ($optionLetters[$i] ?? $i + 1) . '.  ' . ($opt['text'] ?? '');
        $newPara = $paraNode->cloneNode(true);

        $cloneXpath = new DOMXPath($dom);
        $cloneXpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');

        // Collapse all runs into one so there are no leftover split-run fragments.
        // Keep the first run (it carries the styling), remove the rest.
        $runs = $cloneXpath->query('.//a:r', $newPara);
        for ($r = $runs->length - 1; $r >= 1; $r--) {
            $runs->item($r)->parentNode->removeChild($runs->item($r));
        }

        // Set label text on the surviving run's <a:t>
        $firstT = $cloneXpath->query('.//a:t', $newPara)->item(0);
        if ($firstT) {
            $firstT->nodeValue = $label;
        }

        $bodyNode->insertBefore($newPara, $paraNode);
    }

    $bodyNode->removeChild($paraNode);

    return $dom->saveXML();
}

// ── DOM-safe removal of <p:sldId> nodes by rId ───────────────────────
private function removeSldIdsByRids(DOMDocument $doc, array $rIds): void
{
    if (empty($rIds)) return;

    $xpath = new DOMXPath($doc);
    $xpath->registerNamespace('p', 'http://schemas.openxmlformats.org/presentationml/2006/main');
    $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

    foreach ($rIds as $rId) {
        $nodes = $xpath->query('//p:sldId[@r:id="' . $rId . '"]');
        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }
    }
}

public function exportQuizPPT(Request $request, $classId, $lessonId)
{
    $workFile = null;

    try {
        $teacher = Auth::guard('teacher')->user();

        $hasAccess = DB::table('teacher_class_matrix')
            ->where('teacher_id', $teacher->id)
            ->where('class_id', $classId)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'time_limit'    => 'nullable|integer',
            'passing_score' => 'nullable|numeric',
            'questions'     => 'required|array|min:1',
        ]);

        $class     = DB::table('classes')->where('id', $classId)->first();
        $questions = collect($data['questions']);

        $templatePath = storage_path('app/templates/quiz_template.pptx');
        if (!file_exists($templatePath)) {
            return response()->json(['success' => false, 'message' => 'Template not found'], 500);
        }

        $workFile = tempnam(sys_get_temp_dir(), 'quiz_') . '.pptx';
        copy($templatePath, $workFile);

        $zip = new ZipArchive();
        if ($zip->open($workFile) !== true) {
            throw new \Exception('Cannot open PPTX as ZIP archive.');
        }

        // ── Blueprint slide numbers inside the template (1-based) ────────────
        $blueprintSlideNum = [
            'multiple_choice' => 2,
            'multiple_answer' => 2,
            'true_false'      => 3,
            'short_answer'    => 4,
            'essay'           => 5,
        ];
        $totalBlueprintSlides = 5;

        // ── Category helpers ──────────────────────────────────────────────────
        $romanNumerals = ['I','II','III','IV','V','VI','VII','VIII','IX','X'];

        $categoryLabels = [
            'multiple_choice' => 'Multiple Choice',
            'multiple_answer' => 'Multiple Answer',
            'true_false'      => 'True or False',
            'short_answer'    => 'Short Answer',
            'essay'           => 'Essay',
        ];

        // ── Read manifests ────────────────────────────────────────────────────
        $presXml      = $zip->getFromName('ppt/presentation.xml');
        $presRelsXml  = $zip->getFromName('ppt/_rels/presentation.xml.rels');
        $contentTypes = $zip->getFromName('[Content_Types].xml');

        // ── Read the raw title-slide template BEFORE patching ─────────────────
        // This must happen first — once addFromString overwrites slide1.xml in
        // the ZIP, getFromName may return the patched version (tokens already
        // replaced), making them unavailable for category divider slides.
        $titleSlideTemplate = $zip->getFromName('ppt/slides/slide1.xml');
        $titleRels          = $zip->getFromName('ppt/slides/_rels/slide1.xml.rels');

        // ── Build a clean divider rels that only carries the slideLayout ref ──
        // Copying slide1.xml.rels verbatim risks dangling references (notes,
        // hyperlinks, images) that don't exist for the divider slides and cause
        // PowerPoint's "repair" prompt.
        $dividerRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>';

        if ($titleRels !== false) {
            $titleRelsDoc = new DOMDocument();
            $titleRelsDoc->loadXML($titleRels);
            foreach ($titleRelsDoc->getElementsByTagName('Relationship') as $rel) {
                if (str_contains($rel->getAttribute('Type'), '/slideLayout')) {
                    $dividerRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                        . '<Relationship'
                        . ' Id="'     . htmlspecialchars($rel->getAttribute('Id'),     ENT_XML1) . '"'
                        . ' Type="'   . htmlspecialchars($rel->getAttribute('Type'),   ENT_XML1) . '"'
                        . ' Target="' . htmlspecialchars($rel->getAttribute('Target'), ENT_XML1) . '"/>'
                        . '</Relationships>';
                    break;
                }
            }
        }

        // ── Parse rels via DOM (robust against attribute ordering) ────────────
        $relsDoc = new DOMDocument();
        $relsDoc->loadXML($presRelsXml);
        $maxRId        = 0;
        $blueprintRIds = [];

        foreach ($relsDoc->getElementsByTagName('Relationship') as $rel) {
            $rId    = $rel->getAttribute('Id');
            $type   = $rel->getAttribute('Type');
            $target = $rel->getAttribute('Target');
            $num    = (int) filter_var($rId, FILTER_SANITIZE_NUMBER_INT);
            if ($num > $maxRId) $maxRId = $num;

            $isSlide = str_contains($type, '/slide')
                && !str_contains($type, 'Layout')
                && !str_contains($type, 'Master');

            if ($isSlide && $target !== 'slides/slide1.xml') {
                $blueprintRIds[] = $rId;
            }
        }

        // ── Find max sldId via DOM ────────────────────────────────────────────
        $presDoc = new DOMDocument();
        $presDoc->loadXML($presXml);
        $maxSldId = 255;
        foreach ($presDoc->getElementsByTagName('sldId') as $node) {
            $id = (int) $node->getAttribute('id');
            if ($id > $maxSldId) $maxSldId = $id;
        }

        // ── Patch title slide (uses the raw template captured above) ─────────
        $titleXml = $this->replaceTokensInSlideXml($titleSlideTemplate, [
            '{{QUIZ_TITLE}}' => $class->class_name ?? $class->class_code,
            '{{QUIZ_META}}'  => $data['title'],
        ]);
        $zip->addFromString('ppt/slides/slide1.xml', $titleXml);

        // ── Generate slides: category divider + one slide per question ────────
        $nextSlideNum    = $totalBlueprintSlides + 1;
        $addedSlides     = [];
        $currentCategory = null;
        $categoryCount   = 0;

        foreach ($questions as $idx => $q) {
            $type = $q['question_type'] ?? 'essay';

            // ── Category transition slide on every type boundary ──────────────
            if ($type !== $currentCategory) {
                $currentCategory = $type;
                $categoryCount++;

                $romanLabel    = $romanNumerals[min($categoryCount - 1, count($romanNumerals) - 1)];
                $categoryLabel = $categoryLabels[$type] ?? ucwords(str_replace('_', ' ', $type));

                // Use $titleSlideTemplate (raw, tokens intact) — NOT getFromName()
                $catXml = $this->replaceTokensInSlideXml($titleSlideTemplate, [
                    '{{QUIZ_TITLE}}' => 'Test ' . $romanLabel,
                    '{{QUIZ_META}}'  => $categoryLabel,
                ]);

                $catSlideFile = 'ppt/slides/slide' . $nextSlideNum . '.xml';
                $catRelsFile  = 'ppt/slides/_rels/slide' . $nextSlideNum . '.xml.rels';

                $zip->addFromString($catSlideFile, $catXml);
                $zip->addFromString($catRelsFile, $dividerRels);

                $maxRId++;
                $maxSldId++;
                $addedSlides[] = [
                    'rId'    => 'rId' . $maxRId,
                    'target' => 'slides/slide' . $nextSlideNum . '.xml',
                    'sldId'  => $maxSldId,
                    'ctPath' => '/ppt/slides/slide' . $nextSlideNum . '.xml',
                ];

                $nextSlideNum++;
            }

            // ── Question slide ────────────────────────────────────────────────
            $bpNum  = $blueprintSlideNum[$type] ?? 5;
            $bpFile = 'ppt/slides/slide' . $bpNum . '.xml';
            $bpRels = 'ppt/slides/_rels/slide' . $bpNum . '.xml.rels';

            $slideXml  = $zip->getFromName($bpFile);
            $slideRels = $zip->getFromName($bpRels);

            if ($slideXml === false) {
                throw new \Exception("Blueprint slide not found in template: slide{$bpNum}.xml");
            }

            $ptVal   = $q['points'] ?? 1;
            $ptLabel = $ptVal == 1 ? 'pt' : 'pts';

            $tokens = [
                '{{QUESTION_NUMBER}}' => ($idx + 1) . ' (' . $ptVal . ' ' . $ptLabel . ')',
                '{{QUESTION_TEXT}}'   => $q['question_text'] ?? '',
                '{{HINT}}'            => '',
                '{{OPTION_A}}'        => '',
                '{{OPTION_B}}'        => '',
            ];

            if (in_array($type, ['multiple_choice', 'multiple_answer'])) {
                $tokens['{{HINT}}'] = $type === 'multiple_answer'
                    ? '* Select all correct answers'
                    : '';
                $slideXml = $this->replaceTokensInSlideXml($slideXml, $tokens);
                $slideXml = $this->buildOptionParagraphsInSlideXml($slideXml, $q['options'] ?? []);
            } else {
                $tokens['{{OPTIONS}}'] = '';
                if ($type === 'true_false') {
                    $tokens['{{OPTION_A}}'] = 'True';
                    $tokens['{{OPTION_B}}'] = 'False';
                } elseif ($type === 'short_answer') {
                    $tokens['{{HINT}}'] = 'Write your answer on the line provided.';
                }
                $slideXml = $this->replaceTokensInSlideXml($slideXml, $tokens);
            }

            $newSlideFile = 'ppt/slides/slide' . $nextSlideNum . '.xml';
            $newRelsFile  = 'ppt/slides/_rels/slide' . $nextSlideNum . '.xml.rels';

            $zip->addFromString($newSlideFile, $slideXml);
            $zip->addFromString(
                $newRelsFile,
                $slideRels !== false
                    ? $slideRels
                    : '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                      . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>'
            );

            $maxRId++;
            $maxSldId++;
            $addedSlides[] = [
                'rId'    => 'rId' . $maxRId,
                'target' => 'slides/slide' . $nextSlideNum . '.xml',
                'sldId'  => $maxSldId,
                'ctPath' => '/ppt/slides/slide' . $nextSlideNum . '.xml',
            ];

            $nextSlideNum++;
        }

        // ── Update ppt/presentation.xml via DOM ───────────────────────────────
        $this->removeSldIdsByRids($presDoc, $blueprintRIds);
        $presXml = $presDoc->saveXML();

        $newSldIds = '';
        foreach ($addedSlides as $s) {
            $newSldIds .= '<p:sldId id="' . $s['sldId'] . '" r:id="' . $s['rId'] . '"/>';
        }
        $presXml = str_replace('</p:sldIdLst>', $newSldIds . '</p:sldIdLst>', $presXml);
        $zip->addFromString('ppt/presentation.xml', $presXml);

        // ── Update ppt/_rels/presentation.xml.rels via DOM ────────────────────
        foreach ($blueprintRIds as $rId) {
            foreach ($relsDoc->getElementsByTagName('Relationship') as $rel) {
                if ($rel->getAttribute('Id') === $rId) {
                    $rel->parentNode->removeChild($rel);
                    break;
                }
            }
        }
        $slideRelType = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide';
        $relsRoot     = $relsDoc->getElementsByTagName('Relationships')->item(0);
        foreach ($addedSlides as $s) {
            $newRel = $relsDoc->createElement('Relationship');
            $newRel->setAttribute('Id',     $s['rId']);
            $newRel->setAttribute('Type',   $slideRelType);
            $newRel->setAttribute('Target', $s['target']);
            $relsRoot->appendChild($newRel);
        }
        $zip->addFromString('ppt/_rels/presentation.xml.rels', $relsDoc->saveXML());

        // ── Update [Content_Types].xml via DOM ────────────────────────────────
        $ctDoc = new DOMDocument();
        $ctDoc->loadXML($contentTypes);
        $toRemove = [];
        foreach ($ctDoc->getElementsByTagName('Override') as $ov) {
            $part = $ov->getAttribute('PartName');
            for ($i = 2; $i <= $totalBlueprintSlides; $i++) {
                if ($part === '/ppt/slides/slide' . $i . '.xml') {
                    $toRemove[] = $ov;
                    break;
                }
            }
        }
        foreach ($toRemove as $node) {
            $node->parentNode->removeChild($node);
        }
        $slideCT = 'application/vnd.openxmlformats-officedocument.presentationml.slide+xml';
        $ctRoot  = $ctDoc->getElementsByTagName('Types')->item(0);
        foreach ($addedSlides as $s) {
            $newOv = $ctDoc->createElement('Override');
            $newOv->setAttribute('PartName',    $s['ctPath']);
            $newOv->setAttribute('ContentType', $slideCT);
            $ctRoot->appendChild($newOv);
        }
        $zip->addFromString('[Content_Types].xml', $ctDoc->saveXML());

        // ── Delete blueprint slide files from the ZIP ─────────────────────────
        for ($i = 2; $i <= $totalBlueprintSlides; $i++) {
            $zip->deleteName('ppt/slides/slide' . $i . '.xml');
            $zip->deleteName('ppt/slides/_rels/slide' . $i . '.xml.rels');
        }

        $zip->close();

        // ── Stream to client ──────────────────────────────────────────────────
        $filename   = \Str::slug($data['title']) . '_' . now()->format('Ymd_His') . '.pptx';
        $exportDir  = storage_path('app/exports');
        if (!file_exists($exportDir)) mkdir($exportDir, 0755, true);
        $exportPath = $exportDir . '/' . $filename;
        rename($workFile, $exportPath);
        $workFile = null;

        $this->logAudit(
            'exported', 'quiz', null,
            "Exported quiz preview '{$data['title']}' as PowerPoint for class '{$class->class_code}'",
            null,
            [
                'class_id'   => $classId,
                'lesson_id'  => $lessonId,
                'quiz_title' => $data['title'],
                'questions'  => $questions->count(),
                'filename'   => $filename,
            ],
            'teacher', $teacher->email
        );

        return response()->download($exportPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ])->deleteFileAfterSend(true);

    } catch (\Exception $e) {
        if ($workFile && file_exists($workFile)) unlink($workFile);
        \Log::error('Failed to export quiz PPT', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()], 500);
    }
}

    public function teacherIndex($classId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();
        if (!$class) abort(404, 'Class not found');

        return view('modules.quiz.page_quiz', [
            'userType' => 'teacher',
            'class' => $class,
        ]);
    }

    public function teacherCreate($classId, $lessonId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();
        $lesson = DB::table('lessons')
            ->select('lessons.*', 'classes.class_code')
            ->join('classes', 'lessons.class_id', '=', 'classes.id')
            ->where('lessons.id', $lessonId)
            ->where('lessons.class_id', $classId)
            ->first();

        if (!$class || !$lesson) abort(404, 'Class or lesson not found');

        // Get the active semester and its quarters
        $currentSemester = DB::table('semesters')
            ->where('status', 'active')
            ->first();

        if (!$currentSemester) {
            return back()->with('error', 'No active semester found. Please activate a semester first.');
        }

        $quarters = DB::table('quarters')
            ->where('semester_id', $currentSemester->id)
            ->orderBy('order_number')
            ->get();

        // Try to get quarter from lesson's existing quizzes
        $lessonQuarter = DB::table('quizzes')
            ->where('lesson_id', $lessonId)
            ->whereNotNull('quarter_id')
            ->value('quarter_id');

        // Default to first quarter if no existing quiz
        $defaultQuarterId = $lessonQuarter ?? ($quarters->first()->id ?? null);

        return view('modules.quiz.create_quiz', [
            'scripts' => ['class_quiz/teacher_quiz.js'],
            'userType' => 'teacher',
            'class' => $class,
            'lesson' => $lesson,
            'quarters' => $quarters,
            'semesterId' => $currentSemester->id,
            'defaultQuarterId' => $defaultQuarterId,
            'isEdit' => false
        ]);
    }

    public function teacherEdit($classId, $lessonId, $quizId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();
        $lesson = DB::table('lessons')->where('id', $lessonId)->where('class_id', $classId)->first();
        $quiz = DB::table('quizzes')->where('id', $quizId)->where('lesson_id', $lessonId)->first();

        if (!$class || !$lesson || !$quiz) abort(404, 'Resource not found');

        // Get semester from quiz or current active semester
        $semesterId = $quiz->semester_id;
        if (!$semesterId) {
            $semesterId = DB::table('semesters')->where('status', 'active')->value('id');
        }

        $quarters = DB::table('quarters')
            ->where('semester_id', $semesterId)
            ->orderBy('order_number')
            ->get();

        return view('modules.quiz.create_quiz', [
            'scripts' => ['class_quiz/teacher_quiz.js'],
            'userType' => 'teacher',
            'class' => $class,
            'lesson' => $lesson,
            'quiz' => $quiz,
            'quarters' => $quarters,
            'semesterId' => $semesterId,
            'defaultQuarterId' => $quiz->quarter_id,
            'isEdit' => true
        ]);
    }

    public function getQuizData($classId, $lessonId, $quizId)
    {
        try {
            $quiz = DB::table('quizzes')->where('id', $quizId)->where('lesson_id', $lessonId)->first();
            if (!$quiz) return response()->json(['success' => false, 'message' => 'Quiz not found'], 404);

            $questions = DB::table('quiz_questions')->where('quiz_id', $quizId)->orderBy('order_number')->get();

            $questionsData = [];
            foreach ($questions as $question) {
                $qData = [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'points' => $question->points,
                    'order_number' => $question->order_number
                ];

                if (in_array($question->question_type, ['multiple_choice', 'multiple_answer', 'true_false'])) {
                    $qData['options'] = DB::table('quiz_question_options')
                        ->where('question_id', $question->id)
                        ->orderBy('order_number')
                        ->get();
                }

                if ($question->question_type === 'short_answer') {
                    $answers = DB::table('quiz_short_answers')
                        ->where('question_id', $question->id)
                        ->pluck('answer_text')
                        ->toArray();
                    $qData['accepted_answers'] = $answers;
                    $qData['exact_match'] = (bool) $question->exact_match;
                }

                $questionsData[] = $qData;
            }

            return response()->json([
                'success' => true,
                'data' => ['quiz' => $quiz, 'questions' => $questionsData]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request, $classId, $lessonId)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'time_limit' => 'nullable|integer|min:1',
                'available_from' => 'nullable|date_format:Y-m-d\TH:i',
                'available_until' => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:available_from',
                'passing_score' => 'required|numeric|min:0|max:100',
                'max_attempts' => 'required|integer|min:1|max:5',
                'quarter_id' => 'required|integer|exists:quarters,id',
                'semester_id' => 'required|integer|exists:semesters,id',
                'questions' => 'required|array|min:1',
                'questions.*.question_text' => 'required|string',
                'questions.*.question_type' => 'required|in:multiple_choice,multiple_answer,true_false,short_answer',
                'questions.*.points' => 'required|numeric|min:0.01',
                'max_questions' => 'nullable|integer|min:1'

            ]);

            foreach ($request->questions as $i => $q) {
                $type = $q['question_type'];
                
                if (in_array($type, ['multiple_choice', 'multiple_answer', 'true_false'])) {
                    if (!isset($q['options']) || count($q['options']) < 2) {
                        throw new \Exception("Question " . ($i + 1) . " must have at least 2 options");
                    }
                    if (count($q['options']) > 10) {
                        throw new \Exception("Question " . ($i + 1) . " cannot have more than 10 options");
                    }
                    
                    $optTexts = array_map(fn($o) => strtolower(trim($o['text'])), $q['options']);
                    if (count($optTexts) !== count(array_unique($optTexts))) {
                        throw new \Exception("Question " . ($i + 1) . " has duplicate options");
                    }
                    
                    $hasCorrect = array_filter($q['options'], fn($o) => $o['is_correct'] ?? false);
                    if (empty($hasCorrect)) {
                        throw new \Exception("Question " . ($i + 1) . " must have at least one correct answer");
                    }
                }
                
                if ($type === 'short_answer') {
                    if (!isset($q['accepted_answers']) || count($q['accepted_answers']) < 1) {
                        throw new \Exception("Question " . ($i + 1) . " must have at least one accepted answer");
                    }
                    
                    $answers = array_map(fn($a) => strtolower(trim($a)), $q['accepted_answers']);
                    if (count($answers) !== count(array_unique($answers))) {
                        throw new \Exception("Question " . ($i + 1) . " has duplicate accepted answers");
                    }
                }
            }

            if ($request->max_questions !== null && $request->max_questions > count($request->questions)) {
                throw new \Exception("Questions to show ({$request->max_questions}) cannot exceed total questions (" . count($request->questions) . ")");
            }

            // Get related data for audit log
            $class = DB::table('classes')->where('id', $classId)->first();
            $lesson = DB::table('lessons')->where('id', $lessonId)->first();
            $semester = DB::table('semesters')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->where('semesters.id', $request->semester_id)
                ->select('semesters.*', 'school_years.code as sy_code')
                ->first();
            $quarter = DB::table('quarters')->where('id', $request->quarter_id)->first();

            $quizId = DB::table('quizzes')->insertGetId([
                'lesson_id' => $lessonId,
                'semester_id' => $request->semester_id,
                'quarter_id' => $request->quarter_id,
                'title' => $request->title,
                'description' => $request->description,
                'time_limit' => $request->time_limit,
                'available_from' => $request->available_from,
                'available_until' => $request->available_until,
                'passing_score' => $request->passing_score,
                'max_attempts' => $request->max_attempts,
                'show_results' => 1,
                'shuffle_questions' => 1,
                'max_questions' => $request->max_questions,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $totalPoints = 0;
            $questionTypes = [];

            foreach ($request->questions as $index => $qData) {
                $questionId = DB::table('quiz_questions')->insertGetId([
                    'quiz_id' => $quizId,
                    'question_text' => $qData['question_text'],
                    'question_type' => $qData['question_type'],
                    'points' => $qData['points'],
                    'exact_match' => $qData['exact_match'] ?? true,
                    'order_number' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $totalPoints += $qData['points'];
                $questionTypes[$qData['question_type']] = ($questionTypes[$qData['question_type']] ?? 0) + 1;

                if (isset($qData['options']) && is_array($qData['options'])) {
                    foreach ($qData['options'] as $optIndex => $opt) {
                        DB::table('quiz_question_options')->insert([
                            'question_id' => $questionId,
                            'option_text' => $opt['text'],
                            'is_correct' => $opt['is_correct'] ?? 0,
                            'order_number' => $optIndex + 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }

                if ($qData['question_type'] === 'short_answer' && isset($qData['accepted_answers'])) {
                    foreach ($qData['accepted_answers'] as $answer) {
                        DB::table('quiz_short_answers')->insert([
                            'question_id' => $questionId,
                            'answer_text' => trim($answer),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            // Audit Log
            $this->logAudit(
                'created',
                'quizzes',
                (string)$quizId,
                "Created quiz '{$request->title}' for lesson '{$lesson->title}' in class '{$class->class_name}' - {$quarter->name} {$semester->name} {$semester->sy_code}",
                null,
                [
                    'quiz_id' => $quizId,
                    'quiz_title' => $request->title,
                    'lesson_id' => $lessonId,
                    'lesson_title' => $lesson->title,
                    'class_id' => $classId,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'semester_id' => $request->semester_id,
                    'semester_name' => $semester->name,
                    'quarter_id' => $request->quarter_id,
                    'quarter_name' => $quarter->name,
                    'school_year' => $semester->sy_code,
                    'total_questions' => count($request->questions),
                    'total_points' => $totalPoints,
                    'question_types' => $questionTypes,
                    'time_limit' => $request->time_limit,
                    'passing_score' => $request->passing_score,
                    'max_attempts' => $request->max_attempts,
                    'max_questions' => $request->max_questions,
                    'available_from' => $request->available_from,
                    'available_until' => $request->available_until
                ]
            );

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Quiz created successfully', 'data' => ['quiz_id' => $quizId]]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $classId, $lessonId, $quizId)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'quarter_id' => 'required|integer|exists:quarters,id',
                'semester_id' => 'required|integer|exists:semesters,id',
                'available_from' => 'nullable|date',
                'available_until' => 'nullable|date|after_or_equal:available_from',
                'questions' => 'required|array|min:1',
                'max_questions' => 'nullable|integer|min:1',

            ]);

            foreach ($request->questions as $i => $q) {
                $type = $q['question_type'];
                
                if (in_array($type, ['multiple_choice', 'multiple_answer', 'true_false'])) {
                    if (!isset($q['options']) || count($q['options']) < 2) {
                        throw new \Exception("Question " . ($i + 1) . " must have at least 2 options");
                    }
                    if (count($q['options']) > 10) {
                        throw new \Exception("Question " . ($i + 1) . " cannot have more than 10 options");
                    }
                    
                    $optTexts = array_map(fn($o) => strtolower(trim($o['text'])), $q['options']);
                    if (count($optTexts) !== count(array_unique($optTexts))) {
                        throw new \Exception("Question " . ($i + 1) . " has duplicate options");
                    }
                    
                    $hasCorrect = array_filter($q['options'], fn($o) => $o['is_correct'] ?? false);
                    if (empty($hasCorrect)) {
                        throw new \Exception("Question " . ($i + 1) . " must have a correct answer");
                    }
                }
                
                if ($type === 'short_answer') {
                    if (!isset($q['accepted_answers']) || count($q['accepted_answers']) < 1) {
                        throw new \Exception("Question " . ($i + 1) . " needs at least one answer");
                    }
                    
                    $answers = array_map(fn($a) => strtolower(trim($a)), $q['accepted_answers']);
                    if (count($answers) !== count(array_unique($answers))) {
                        throw new \Exception("Question " . ($i + 1) . " has duplicate answers");
                    }
                }
            }

            if ($request->max_questions !== null && $request->max_questions > count($request->questions)) {
                throw new \Exception("Questions to show ({$request->max_questions}) cannot exceed total questions (" . count($request->questions) . ")");
            }

            // Get old quiz data for audit log
            $oldQuiz = DB::table('quizzes')->where('id', $quizId)->first();
            $oldQuestions = DB::table('quiz_questions')->where('quiz_id', $quizId)->get();
            
            $oldTotalPoints = $oldQuestions->sum('points');
            $oldQuestionTypes = [];
            foreach ($oldQuestions as $q) {
                $oldQuestionTypes[$q->question_type] = ($oldQuestionTypes[$q->question_type] ?? 0) + 1;
            }

            // Get related data for audit log
            $class = DB::table('classes')->where('id', $classId)->first();
            $lesson = DB::table('lessons')->where('id', $lessonId)->first();
            $semester = DB::table('semesters')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->where('semesters.id', $request->semester_id)
                ->select('semesters.*', 'school_years.code as sy_code')
                ->first();
            $quarter = DB::table('quarters')->where('id', $request->quarter_id)->first();

            DB::table('quizzes')->where('id', $quizId)->where('lesson_id', $lessonId)->update([
                'title' => $request->title,
                'description' => $request->description,
                'time_limit' => $request->time_limit,
                'available_from' => $request->available_from,
                'available_until' => $request->available_until,
                'passing_score' => $request->passing_score,
                'max_attempts' => $request->max_attempts,
                'semester_id' => $request->semester_id,
                'quarter_id' => $request->quarter_id,
                'max_questions' => $request->max_questions,
                'updated_at' => now()
            ]);

            $qIds = DB::table('quiz_questions')->where('quiz_id', $quizId)->pluck('id');
            if ($qIds->count() > 0) {
                DB::table('quiz_question_options')->whereIn('question_id', $qIds)->delete();
                DB::table('quiz_short_answers')->whereIn('question_id', $qIds)->delete();
            }
            DB::table('quiz_questions')->where('quiz_id', $quizId)->delete();

            $totalPoints = 0;
            $questionTypes = [];

            foreach ($request->questions as $index => $qData) {
                $questionId = DB::table('quiz_questions')->insertGetId([
                    'quiz_id' => $quizId,
                    'question_text' => $qData['question_text'],
                    'question_type' => $qData['question_type'],
                    'points' => $qData['points'],
                    'exact_match' => $qData['exact_match'] ?? true,
                    'order_number' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $totalPoints += $qData['points'];
                $questionTypes[$qData['question_type']] = ($questionTypes[$qData['question_type']] ?? 0) + 1;

                if (isset($qData['options'])) {
                    foreach ($qData['options'] as $optIndex => $opt) {
                        DB::table('quiz_question_options')->insert([
                            'question_id' => $questionId,
                            'option_text' => $opt['text'],
                            'is_correct' => $opt['is_correct'] ?? 0,
                            'order_number' => $optIndex + 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }

                if ($qData['question_type'] === 'short_answer' && isset($qData['accepted_answers'])) {
                    foreach ($qData['accepted_answers'] as $answer) {
                        DB::table('quiz_short_answers')->insert([
                            'question_id' => $questionId,
                            'answer_text' => trim($answer),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            // Audit Log
            $this->logAudit(
                'updated',
                'quizzes',
                (string)$quizId,
                "Updated quiz '{$request->title}' for lesson '{$lesson->title}' in class '{$class->class_name}' - {$quarter->name} {$semester->name} {$semester->sy_code}",
                [
                    'quiz_title' => $oldQuiz->title,
                    'total_questions' => $oldQuestions->count(),
                    'total_points' => $oldTotalPoints,
                    'question_types' => $oldQuestionTypes,
                    'time_limit' => $oldQuiz->time_limit,
                    'passing_score' => $oldQuiz->passing_score,
                    'max_attempts' => $oldQuiz->max_attempts,
                    'semester_id' => $oldQuiz->semester_id,
                    'quarter_id' => $oldQuiz->quarter_id,
                    'available_from' => $oldQuiz->available_from,
                    'available_until' => $oldQuiz->available_until,
                    'max_questions' => $oldQuiz->max_questions,
                ],
                [
                    'quiz_title' => $request->title,
                    'lesson_id' => $lessonId,
                    'lesson_title' => $lesson->title,
                    'class_id' => $classId,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'semester_id' => $request->semester_id,
                    'semester_name' => $semester->name,
                    'quarter_id' => $request->quarter_id,
                    'quarter_name' => $quarter->name,
                    'school_year' => $semester->sy_code,
                    'total_questions' => count($request->questions),
                    'total_points' => $totalPoints,
                    'question_types' => $questionTypes,
                    'time_limit' => $request->time_limit,
                    'passing_score' => $request->passing_score,
                    'max_attempts' => $request->max_attempts,
                    'available_from' => $request->available_from,
                    'available_until' => $request->available_until,
                    'max_questions' => $request->max_questions,
                ]
            );

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Quiz updated successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($classId, $lessonId, $quizId)
    {
        try {
            // Get quiz data before deletion for audit log
            $quiz = DB::table('quizzes')
                ->where('id', $quizId)
                ->where('lesson_id', $lessonId)
                ->first();

            if (!$quiz) {
                return response()->json(['success' => false, 'message' => 'Quiz not found'], 404);
            }

            // Get related data for audit log
            $class = DB::table('classes')
                ->join('lessons', 'classes.id', '=', 'lessons.class_id')
                ->where('lessons.id', $lessonId)
                ->select('classes.*')
                ->first();
            
            $lesson = DB::table('lessons')->where('id', $lessonId)->first();
            
            $semester = DB::table('semesters')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->where('semesters.id', $quiz->semester_id)
                ->select('semesters.*', 'school_years.code as sy_code')
                ->first();
            
            $quarter = DB::table('quarters')->where('id', $quiz->quarter_id)->first();

            $questionCount = DB::table('quiz_questions')->where('quiz_id', $quizId)->count();

            DB::table('quizzes')->where('id', $quizId)->where('lesson_id', $lessonId)->update([
                'status' => 0,
                'updated_at' => now()
            ]);

            // Audit Log
            $this->logAudit(
                'deleted',
                'quizzes',
                (string)$quizId,
                "Deleted quiz '{$quiz->title}' from lesson '{$lesson->title}' in class '{$class->class_name}' - {$quarter->name} {$semester->name} {$semester->sy_code}",
                [
                    'quiz_id' => $quizId,
                    'quiz_title' => $quiz->title,
                    'total_questions' => $questionCount,
                    'status' => 1
                ],
                [
                    'status' => 0,
                    'lesson_id' => $lessonId,
                    'lesson_title' => $lesson->title,
                    'class_id' => $classId,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'semester_id' => $quiz->semester_id,
                    'semester_name' => $semester->name,
                    'quarter_id' => $quiz->quarter_id,
                    'quarter_name' => $quarter->name,
                    'school_year' => $semester->sy_code
                ]
            );

            return response()->json(['success' => true, 'message' => 'Quiz deleted']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function checkAvailability($classId, $lessonId, $quizId)
    {
        try {
            $quiz = DB::table('quizzes')
                ->where('id', $quizId)
                ->where('lesson_id', $lessonId)
                ->where('status', 1)
                ->first();

            if (!$quiz) {
                return response()->json(['available' => false, 'message' => 'Quiz not found'], 404);
            }

            $now = now();
            $isAvailable = true;
            $message = '';

            if ($quiz->available_from && $now->lt($quiz->available_from)) {
                $isAvailable = false;
                $message = 'Quiz will be available from ' . date('F j, Y g:i A', strtotime($quiz->available_from));
            } elseif ($quiz->available_until && $now->gt($quiz->available_until)) {
                $isAvailable = false;
                $message = 'Quiz closed on ' . date('F j, Y g:i A', strtotime($quiz->available_until));
            }

            return response()->json([
                'available' => $isAvailable,
                'message' => $message,
                'available_from' => $quiz->available_from,
                'available_until' => $quiz->available_until
            ]);
        } catch (\Exception $e) {
            return response()->json(['available' => false, 'message' => 'Error checking availability'], 500);
        }
    }
}