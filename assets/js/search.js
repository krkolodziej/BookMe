document.addEventListener('DOMContentLoaded', function() {
    const serviceSearchInput = document.getElementById('serviceSearch');
    const citySearchInput = document.getElementById('citySearch');
    const serviceSuggestions = document.getElementById('serviceSuggestions');
    const citySuggestions = document.getElementById('citySuggestions');

    // Function to clear input
    window.clearInput = function(inputId) {
        document.getElementById(inputId).value = '';
        if (inputId === 'serviceSearch') {
            serviceSuggestions.innerHTML = '';
            serviceSuggestions.classList.remove('p-2', 'border', 'rounded', 'suggestions-container');
            serviceSuggestions.style.position = '';
            serviceSuggestions.style.width = '';
            serviceSuggestions.style.top = '';
            serviceSuggestions.style.left = '';
        } else if (inputId === 'citySearch') {
            citySuggestions.innerHTML = '';
            citySuggestions.classList.remove('p-2', 'border', 'rounded', 'suggestions-container');
            citySuggestions.style.position = '';
            citySuggestions.style.width = '';
            citySuggestions.style.top = '';
            citySuggestions.style.left = '';
        }
    };

    // Obsługa klawisza Escape do zamykania sugestii
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            serviceSuggestions.innerHTML = '';
            serviceSuggestions.classList.remove('p-2', 'border', 'rounded', 'suggestions-container');
            serviceSuggestions.style.position = '';
            serviceSuggestions.style.width = '';
            serviceSuggestions.style.top = '';
            serviceSuggestions.style.left = '';

            citySuggestions.innerHTML = '';
            citySuggestions.classList.remove('p-2', 'border', 'rounded', 'suggestions-container');
            citySuggestions.style.position = '';
            citySuggestions.style.width = '';
            citySuggestions.style.top = '';
            citySuggestions.style.left = '';
        }
    });

    // Function to handle suggestions
    const handleSuggestions = (input, suggestionsElement, endpoint) => {
        let debounceTimer;

        input.addEventListener('input', function() {
            const term = this.value.trim();

            // Clear previous timer
            clearTimeout(debounceTimer);

            if (term.length === 0) {
                suggestionsElement.innerHTML = '';
                suggestionsElement.classList.remove('p-2', 'border', 'rounded', 'suggestions-container');
                return;
            }

            // Set new timer (250ms debounce - szybsza reakcja)
            debounceTimer = setTimeout(() => {
                // Ustaw pozycję sugestii względem pola input
                const inputRect = input.getBoundingClientRect();
                suggestionsElement.style.position = 'fixed';
                suggestionsElement.style.width = `${inputRect.width}px`;
                suggestionsElement.style.top = `${inputRect.bottom + window.scrollY}px`;
                suggestionsElement.style.left = `${inputRect.left + window.scrollX}px`;

                // Make AJAX request for suggestions
                fetch(`/search/${endpoint}?term=${encodeURIComponent(term)}`)
                    .then(response => response.json())
                    .then(data => {
                        // Clear previous suggestions
                        suggestionsElement.innerHTML = '';

                        if (data.length === 0) {
                            return;
                        }

                        // Add suggestions container class with stronger styling
                        suggestionsElement.classList.add('p-2', 'border', 'rounded', 'suggestions-container');

                        // Create suggestion items
                        data.forEach(item => {
                            const suggestionItem = document.createElement('div');
                            suggestionItem.className = 'suggestion-item p-2';

                            // Highlight the matching part of the text
                            const lowerItem = item.toLowerCase();
                            const lowerTerm = term.toLowerCase();
                            const index = lowerItem.indexOf(lowerTerm);

                            if (index !== -1) {
                                const before = item.substring(0, index);
                                const match = item.substring(index, index + term.length);
                                const after = item.substring(index + term.length);

                                suggestionItem.innerHTML = before + '<strong>' + match + '</strong>' + after;
                            } else {
                                suggestionItem.textContent = item;
                            }

                            // Handle click on suggestion
                            suggestionItem.addEventListener('click', function() {
                                input.value = item;
                                suggestionsElement.innerHTML = '';
                                suggestionsElement.classList.remove('p-2', 'border', 'rounded', 'suggestions-container');
                                // Reset position
                                suggestionsElement.style.position = '';
                                suggestionsElement.style.width = '';
                                suggestionsElement.style.top = '';
                                suggestionsElement.style.left = '';
                            });

                            suggestionsElement.appendChild(suggestionItem);
                        });

                        // Add event to close suggestions when clicking outside
                        document.addEventListener('click', function(event) {
                            if (!input.contains(event.target) && !suggestionsElement.contains(event.target)) {
                                suggestionsElement.innerHTML = '';
                                suggestionsElement.classList.remove('p-2', 'border', 'rounded', 'suggestions-container');
                                // Reset position
                                suggestionsElement.style.position = '';
                                suggestionsElement.style.width = '';
                                suggestionsElement.style.top = '';
                                suggestionsElement.style.left = '';
                            }
                        });
                    })
                    .catch(error => console.error('Error fetching suggestions:', error));
            }, 250);
        });
    };

    // Setup event listeners
    if (serviceSearchInput && serviceSuggestions) {
        handleSuggestions(serviceSearchInput, serviceSuggestions, 'offers');
    }

    if (citySearchInput && citySuggestions) {
        handleSuggestions(citySearchInput, citySuggestions, 'cities');
    }
});