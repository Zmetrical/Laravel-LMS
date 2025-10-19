console.log("insert user");

$(document).ready(function () {
    const strandSelect = $('#strand');
    const levelSelect = $('#level');
    const sectionSelect = $('#section');

    function fetchSections() {
        const strandId = strandSelect.val();
        const levelId = levelSelect.val();

        // Reset section dropdown
        sectionSelect.html('<option hidden disabled selected>Select Section</option>');

        // Build query dynamically
        const params = {};
        if (strandId) params.strand_id = strandId;
        if (levelId) params.level_id = levelId;

        if (Object.keys(params).length > 0) {
            $.ajax({
                url: '/sections',
                method: 'GET',
                data: params,
                success: function (data) {
                    if (data.length > 0) {
                        data.forEach(section => {
                            sectionSelect.append(
                                $('<option>', { value: section.id, text: section.name })
                            );
                        });
                    } else {
                        sectionSelect.append(
                            $('<option>', { text: 'No sections available', disabled: true })
                        );
                    }
                },
                error: function () {
                    sectionSelect.append(
                        $('<option>', { text: 'Error loading sections', disabled: true })
                    );
                }
            });
        } else {
            sectionSelect.append(
                $('<option>', { text: 'Please select strand and level first', disabled: true })
            );
        }
    }

    strandSelect.on('change', fetchSections);
    levelSelect.on('change', fetchSections);

});
