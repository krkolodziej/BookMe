document.addEventListener('DOMContentLoaded', function() {
    
    const searchInput = document.getElementById(tableConfig.searchInputId);
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById(tableConfig.tableId);
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const nameCell = rows[i].getElementsByTagName('td')[0];
                const durationCell = rows[i].getElementsByTagName('td')[1];
                const priceCell = rows[i].getElementsByTagName('td')[2];

                if (nameCell || durationCell || priceCell) {
                    const nameValue = nameCell.textContent || nameCell.innerText;
                    const durationValue = durationCell.textContent || durationCell.innerText;
                    const priceValue = priceCell.textContent || priceCell.innerText;

                    if (nameValue.toLowerCase().indexOf(searchValue) > -1 ||
                        durationValue.toLowerCase().indexOf(searchValue) > -1 ||
                        priceValue.toLowerCase().indexOf(searchValue) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        });
    }
});
