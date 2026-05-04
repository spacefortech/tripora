(function () {
    var cities = window.CITY_GUIDE_DATA || [];
    var form = document.querySelector('[data-city-search]');
    var input = document.querySelector('[data-city-input]');
    var cityOptions = document.querySelector('[data-city-options]');
    var cityStrip = document.querySelector('[data-city-strip]');
    var result = document.querySelector('[data-city-result]');
    var heading = document.querySelector('[data-results-heading]');
    var copy = document.querySelector('[data-results-copy]');
    var miniSearch = document.querySelector('[data-mini-search]');
    var miniInput = document.getElementById('mini-city-input');
    var categoryButtons = document.querySelectorAll('[data-category-filter]');
    var activeOptionIndex = 0;
    var visibleOptions = [];

    function normalize(value) {
        var replacements = {
            'ä': 'ae',
            'ö': 'oe',
            'ü': 'ue',
            'ß': 'ss'
        };

        return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/[äöüß]/g, function (match) {
                return replacements[match] || match;
            })
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function escapeHtml(value) {
        var node = document.createElement('span');
        node.textContent = value;
        return node.innerHTML;
    }

    function setSearchCity(city) {
        input.value = city ? city.displayName : '';
        if (miniInput) {
            miniInput.value = city ? city.displayName : '';
        }
    }

    function getSpritePosition(city, fallbackIndex) {
        var imageIndex = typeof city.imageIndex === 'number' ? city.imageIndex : fallbackIndex;
        var column = imageIndex % 4;
        var row = Math.floor(imageIndex / 4);

        return {
            x: column === 3 ? 100 : column * 33.3333,
            y: row === 3 ? 100 : row * 33.3333
        };
    }

    function findCity(value) {
        var normalized = normalize(value);

        for (var i = 0; i < cities.length; i += 1) {
            var city = cities[i];
            var names = [city.slug, city.name, city.displayName].concat(city.aliases || []);

            for (var j = 0; j < names.length; j += 1) {
                if (normalize(names[j]) === normalized) {
                    return city;
                }
            }
        }

        return null;
    }

    function getCityOptions(value) {
        var normalized = normalize(value);

        if (!normalized) {
            return cities.slice();
        }

        return cities.filter(function (city) {
            var names = [city.slug, city.name, city.displayName].concat(city.aliases || []);

            return names.some(function (name) {
                return normalize(name).indexOf(normalized) !== -1;
            });
        });
    }

    function activateCity(slug) {
        var activeSlug = slug || '';
        var selectors = ['[data-city-button]', '[data-city-card]', '[data-destination-card]'];

        selectors.forEach(function (selector) {
            var nodes = document.querySelectorAll(selector);

            nodes.forEach(function (node) {
                node.classList.toggle('is-active', node.getAttribute('data-city-slug') === activeSlug);
            });
        });
    }

    function updateActiveOption() {
        var buttons = cityOptions.querySelectorAll('[data-city-option]');

        buttons.forEach(function (button, index) {
            var isActive = index === activeOptionIndex;

            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');

            if (isActive) {
                input.setAttribute('aria-activedescendant', button.id);
            }
        });
    }

    function renderOptions(value) {
        visibleOptions = getCityOptions(value);
        activeOptionIndex = visibleOptions.length ? Math.max(0, Math.min(activeOptionIndex, visibleOptions.length - 1)) : -1;

        if (!visibleOptions.length) {
            cityOptions.innerHTML = '<div class="city-options-label">Ort</div>' +
                '<div class="city-option-empty">Keine Stadt gefunden</div>';
            input.removeAttribute('aria-activedescendant');
            return;
        }

        cityOptions.innerHTML = '<div class="city-options-label">Ort</div>' +
            visibleOptions.map(function (city, index) {
                return '<button class="city-option' + (index === activeOptionIndex ? ' is-active' : '') + '" id="city-option-' + escapeHtml(city.slug) + '" data-city-option data-city-slug="' + escapeHtml(city.slug) + '" type="button" role="option" aria-selected="' + (index === activeOptionIndex ? 'true' : 'false') + '">' +
                    '<span class="city-option-pin" aria-hidden="true"></span>' +
                    '<span><strong>' + escapeHtml(city.displayName) + '</strong></span>' +
                '</button>';
            }).join('');

        updateActiveOption();
    }

    function openOptions() {
        renderOptions(input.value);
        form.classList.add('is-options-open');
        input.setAttribute('aria-expanded', 'true');
    }

    function closeOptions() {
        form.classList.remove('is-options-open');
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
    }

    function selectOption(city) {
        if (!city) {
            return;
        }

        setSearchCity(city);
        renderCity(city);
        closeOptions();
    }

    function renderCityStrip() {
        cityStrip.innerHTML = cities.map(function (city) {
            return '<button class="city-card" data-city-card data-city-slug="' + escapeHtml(city.slug) + '" type="button">' +
                '<strong>' + escapeHtml(city.displayName) + '</strong>' +
                '<span>' + escapeHtml(city.region) + '</span>' +
            '</button>';
        }).join('');

        var cards = cityStrip.querySelectorAll('[data-city-card]');

        cards.forEach(function (card) {
            card.addEventListener('click', function () {
                var city = findCity(card.getAttribute('data-city-slug'));
                if (city) {
                    setSearchCity(city);
                    renderCity(city);
                    document.getElementById('places').scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    function renderDestinationCard(city, index, activeSlug) {
        var position = getSpritePosition(city, index);
        var isActive = city.slug === activeSlug;
        var neighborhoods = (city.neighborhoods || []).slice(0, 2).join(' / ');

        return '<a class="destination-card' + (isActive ? ' is-active' : '') + '" href="?city=' + encodeURIComponent(city.slug) + '" data-destination-card data-city-slug="' + escapeHtml(city.slug) + '" aria-label="City Guide ' + escapeHtml(city.displayName) + ' ansehen" style="--sprite-x: ' + position.x + '%; --sprite-y: ' + position.y + '%; --city-accent: ' + escapeHtml(city.accent || '#ff7a1a') + ';">' +
            '<span class="destination-photo" aria-hidden="true"></span>' +
            '<span class="destination-shade" aria-hidden="true"></span>' +
            '<span class="destination-content">' +
                '<span class="destination-top">' +
                    '<span class="destination-kicker">' + escapeHtml(city.region) + '</span>' +
                    '<span class="destination-duration">' + escapeHtml(city.duration) + '</span>' +
                '</span>' +
                '<span class="destination-copy">' +
                    '<span class="destination-neighborhoods">' + escapeHtml(neighborhoods) + '</span>' +
                    '<strong>' + escapeHtml(city.displayName) + '</strong>' +
                    '<span class="destination-description">' + escapeHtml(city.bestFor) + '</span>' +
                    '<span class="destination-action">Guide ansehen</span>' +
                '</span>' +
            '</span>' +
        '</a>';
    }

    function bindDestinationCards() {
        var destinationCards = result.querySelectorAll('[data-destination-card]');

        destinationCards.forEach(function (card) {
            card.addEventListener('click', function (event) {
                var city = findCity(card.getAttribute('data-city-slug'));

                if (!city) {
                    return;
                }

                event.preventDefault();
                setSearchCity(city);
                renderCity(city);
            });
        });
    }

    function renderDestinationGrid(activeCity, notice) {
        var activeSlug = activeCity ? activeCity.slug : '';
        var destinationCards = cities.slice(0, 16).map(function (city, index) {
            return renderDestinationCard(city, index, activeSlug);
        }).join('');

        heading.textContent = notice ? 'Stadt nicht gefunden' : 'Städte für deine nächste Reise';
        copy.textContent = notice || 'Sechzehn kuratierte City-Trips in Deutschland, mit starken Motiven, klaren Reisethemen und genug Inspiration für dein nächstes Wochenende.';
        activateCity(activeSlug);
        result.innerHTML = (notice ? '<div class="empty-state"><strong>Keine passende Stadt gefunden.</strong><p>Wähle eine der kuratierten Städte unten oder suche nach einer anderen Schreibweise.</p></div>' : '') +
            '<div class="destinations-grid">' + destinationCards + '</div>';
        bindDestinationCards();
    }

    function renderCity(city) {
        renderDestinationGrid(city || null, '');

        if (city) {
            window.history.replaceState({}, '', '?city=' + encodeURIComponent(city.slug));
            return;
        }

        window.history.replaceState({}, '', window.location.pathname);
    }

    function searchFrom(value, scroll) {
        var normalized = normalize(value);
        var city = findCity(value);

        if (!normalized) {
            setSearchCity(null);
            renderCity(null);
        } else if (city) {
            setSearchCity(city);
            renderCity(city);
        } else {
            input.value = value;
            if (miniInput) {
                miniInput.value = value;
            }
            renderDestinationGrid(null, 'Versuche Berlin, Hamburg, München, Köln, Frankfurt, Dresden, Leipzig, Stuttgart, Nürnberg, Heidelberg, Bremen, Düsseldorf, Duisburg, Bonn, Münster oder Rostock.');
        }

        if (scroll) {
            document.getElementById('places').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function handleSearch(event, sourceInput) {
        event.preventDefault();
        closeOptions();
        searchFrom(sourceInput.value, true);
    }

    function handleOptionKeys(event) {
        if ((event.key === 'ArrowDown' || event.key === 'ArrowUp') && !form.classList.contains('is-options-open')) {
            openOptions();
        }

        if (!form.classList.contains('is-options-open')) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            activeOptionIndex = Math.min(activeOptionIndex + 1, visibleOptions.length - 1);
            updateActiveOption();
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            activeOptionIndex = Math.max(activeOptionIndex - 1, 0);
            updateActiveOption();
        }

        if (event.key === 'Enter' && visibleOptions.length) {
            event.preventDefault();
            selectOption(visibleOptions[activeOptionIndex]);
        }

        if (event.key === 'Escape') {
            closeOptions();
        }
    }

    function bindCategoryFilters() {
        categoryButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                categoryButtons.forEach(function (node) {
                    node.classList.remove('is-active');
                });
                button.classList.add('is-active');
                renderCity(findCity(input.value));
            });
        });
    }

    function initialize() {
        if (!cities.length) {
            return;
        }

        renderOptions('');
        renderCityStrip();
        bindCategoryFilters();

        var params = new URLSearchParams(window.location.search);
        var initialCity = findCity(params.get('city'));
        setSearchCity(null);
        renderCity(initialCity);

        cityOptions.addEventListener('click', function (event) {
            var button = event.target.closest('[data-city-option]');

            if (!button) {
                return;
            }

            selectOption(findCity(button.getAttribute('data-city-slug')));
        });

        input.addEventListener('focus', openOptions);
        input.addEventListener('click', openOptions);
        input.addEventListener('input', function () {
            activeOptionIndex = 0;
            openOptions();
        });
        input.addEventListener('keydown', handleOptionKeys);

        document.addEventListener('click', function (event) {
            if (!form.contains(event.target)) {
                closeOptions();
            }
        });

        form.addEventListener('submit', function (event) {
            handleSearch(event, input);
        });

        if (miniSearch) {
            miniSearch.addEventListener('submit', function (event) {
                handleSearch(event, miniInput);
            });
        }
    }

    initialize();
}());
