document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById(tableConfig.searchInputId);
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById(tableConfig.tableId);
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const nameCell = rows[i].getElementsByTagName('td')[0];
                const contentCell = rows[i].getElementsByTagName('td')[2];
                const employeeCell = rows[i].getElementsByTagName('td')[3];
                const offerCell = rows[i].getElementsByTagName('td')[4];

                if (nameCell || contentCell || employeeCell || offerCell) {
                    const nameValue = nameCell.textContent || nameCell.innerText;
                    const contentValue = contentCell.textContent || contentCell.innerText;
                    const employeeValue = employeeCell.textContent || employeeCell.innerText;
                    const offerValue = offerCell.textContent || offerCell.innerText;

                    if (nameValue.toLowerCase().indexOf(searchValue) > -1 ||
                        contentValue.toLowerCase().indexOf(searchValue) > -1 ||
                        employeeValue.toLowerCase().indexOf(searchValue) > -1 ||
                        offerValue.toLowerCase().indexOf(searchValue) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        });
    }
});
