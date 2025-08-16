document.addEventListener('DOMContentLoaded', function() {
   

    const searchInput = document.getElementById(tableConfig.searchInputId);
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById(tableConfig.tableId);
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const nameCell = rows[i].getElementsByTagName('td')[0];
                const emailCell = rows[i].getElementsByTagName('td')[1];
                const genderCell = rows[i].getElementsByTagName('td')[2];

                if (nameCell || emailCell || genderCell) {
                    const nameValue = nameCell.textContent || nameCell.innerText;
                    const emailValue = emailCell.textContent || emailCell.innerText;
                    const genderValue = genderCell.textContent || genderCell.innerText;

                    if (nameValue.toLowerCase().indexOf(searchValue) > -1 ||
                        emailValue.toLowerCase().indexOf(searchValue) > -1 ||
                        genderValue.toLowerCase().indexOf(searchValue) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        });
    }
});
