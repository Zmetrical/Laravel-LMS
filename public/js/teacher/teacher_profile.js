$(document).ready(function () {
    // Handle expand/collapse button clicks for classes
    $(document).on('click', '.expand-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const classId = $(this).data('class-id');
        const mainRow = $(this).closest('tr');
        const existingDetailRow = mainRow.next('.classes-detail-row');
        const isExpanded = $(this).hasClass('expanded');
        
        if (isExpanded) {
            // Collapse
            $(this).removeClass('expanded');
            existingDetailRow.remove();
        } else {
            // Expand - create and insert detail row
            $(this).addClass('expanded');
            
            // Get sections data from the main row
            const sectionsData = mainRow.data('sections');
            
            // Build sections HTML
            let sectionsHtml = '';
            if (sectionsData) {
                const sections = sectionsData.split(', ');
                sections.forEach(function(section) {
                    sectionsHtml += `<span class="section-badge">${section}</span>`;
                });
            } else {
                sectionsHtml = '<small class="text-muted"><i class="fas fa-info-circle mr-1"></i>No sections assigned</small>';
            }
            
            // Create detail row
            const detailRow = $(`
                <tr class="classes-detail-row">
                    <td colspan="3" class="classes-detail-cell">
                        <div>
                            ${sectionsHtml}
                        </div>
                    </td>
                </tr>
            `);
            
            // Insert after main row
            mainRow.after(detailRow);
        }
    });
});