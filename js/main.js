document.addEventListener('DOMContentLoaded', () => {
    const searchInputs = document.querySelectorAll(
        '[data-search-suggestions]'
    );

    searchInputs.forEach((input) => {
        const wrapper = input.closest('.search-input-wrapper');
        const suggestionBox = wrapper?.querySelector(
            '.search-suggestions'
        );

        if (!suggestionBox) {
            return;
        }

        let requestTimer = null;
        let currentRequest = null;

        const closeSuggestions = () => {
            suggestionBox.hidden = true;
            suggestionBox.innerHTML = '';
            input.setAttribute('aria-expanded', 'false');
        };

        const displayStatus = (message) => {
            suggestionBox.innerHTML = '';

            const status = document.createElement('div');

            status.className = 'search-suggestion-status';
            status.setAttribute('role', 'status');
            status.textContent = message;

            suggestionBox.appendChild(status);
            suggestionBox.hidden = false;

            input.setAttribute('aria-expanded', 'true');
        };

        const displaySuggestions = (suggestions) => {
            suggestionBox.innerHTML = '';

            if (!Array.isArray(suggestions)) {
                displayStatus('Suggestions are temporarily unavailable.');
                return;
            }

            if (suggestions.length === 0) {
                displayStatus('No suggestions found.');
                return;
            }

            suggestions.forEach((suggestion) => {
                const link = document.createElement('a');
                const label = document.createElement('span');
                const type = document.createElement('span');

                link.className = 'search-suggestion';
                link.href = suggestion.url;
                link.setAttribute('role', 'option');

                label.className = 'search-suggestion-label';
                label.textContent = suggestion.label;

                type.className = 'search-suggestion-type';
                type.textContent = suggestion.type;

                link.append(label, type);
                suggestionBox.appendChild(link);
            });

            suggestionBox.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        };

        input.setAttribute('aria-expanded', 'false');

        input.addEventListener('input', () => {
            const query = input.value.trim();
            const suggestionType =
                input.dataset.suggestionType || 'all';

            clearTimeout(requestTimer);

            if (currentRequest) {
                currentRequest.abort();
            }

            if (query.length === 0) {
                closeSuggestions();
                return;
            }

            displayStatus('Searching...');

            requestTimer = setTimeout(async () => {
                currentRequest = new AbortController();

                try {
                    const response = await fetch(
                        '/snapspace/search-suggestions.php?q=' +
                        encodeURIComponent(query) +
                        '&type=' +
                        encodeURIComponent(suggestionType),
                        {
                            signal: currentRequest.signal,
                            headers: {
                                Accept: 'application/json'
                            }
                        }
                    );

                    if (!response.ok) {
                        throw new Error('Suggestion request failed.');
                    }

                    const suggestions = await response.json();
                    displaySuggestions(suggestions);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        displayStatus(
                            'Suggestions are temporarily unavailable.'
                        );
                    }
                }
            }, 250);
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeSuggestions();
            }
        });

        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) {
                closeSuggestions();
            }
        });
    });
});