(function () {
    var cities = window.CITY_GUIDE_DATA || [];
    var form = document.querySelector('[data-city-search]');
    var input = document.querySelector('[data-city-input]');
    var datalist = document.getElementById('city-options');
    var cityStrip = document.querySelector('[data-city-strip]');
    var result = document.querySelector('[data-city-result]');
    var heading = document.querySelector('[data-results-heading]');
    var copy = document.querySelector('[data-results-copy]');
    var miniSearch = document.querySelector('[data-mini-search]');
    var miniInput = document.getElementById('mini-city-input');
    var categoryButtons = document.querySelectorAll('[data-category-filter]');
    var activeCategory = 'all';

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

    function findCity(value) {
        var normalized = normalize(value);

        for (var i = 0; i < cities.length; i += 1) {
            var city = cities[i];
            var names = [city.slug, city.name, city.italianName].concat(city.aliases || []);

            for (var j = 0; j < names.length; j += 1) {
                if (normalize(names[j]) === normalized) {
                    return city;
                }
            }
        }

        return null;
    }

    function activateCity(slug) {
        var activeSlug = slug || '';
        var selectors = ['[data-city-button]', '[data-city-card]'];

        selectors.forEach(function (selector) {
            var nodes = document.querySelectorAll(selector);

            nodes.forEach(function (node) {
                node.classList.toggle('is-active', node.getAttribute('data-city-slug') === activeSlug);
            });
        });
    }

    function renderOptions() {
        datalist.innerHTML = cities.map(function (city) {
            return '<option value="' + escapeHtml(city.italianName) + '"></option>' +
                '<option value="' + escapeHtml(city.name) + '"></option>';
        }).join('');
    }

    function renderCityStrip() {
        cityStrip.innerHTML = cities.map(function (city) {
            return '<button class="city-card" data-city-card data-city-slug="' + escapeHtml(city.slug) + '" type="button">' +
                '<strong>' + escapeHtml(city.italianName) + '</strong>' +
                '<span>' + escapeHtml(city.region) + '</span>' +
            '</button>';
        }).join('');

        var cards = cityStrip.querySelectorAll('[data-city-card]');

        cards.forEach(function (card) {
            card.addEventListener('click', function () {
                var city = findCity(card.getAttribute('data-city-slug'));
                if (city) {
                    input.value = city.italianName;
                    renderCity(city);
                    document.getElementById('places').scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    function renderCity(city) {
        if (!city) {
            heading.textContent = 'Citt\u00e0 non trovata';
            copy.textContent = 'Prova Berlino, Amburgo, Monaco, Colonia, Francoforte o Dresda.';
            result.innerHTML = '<div class="empty-state"><strong>Nessuna citt\u00e0 trovata.</strong><p>Prova Berlino, Amburgo, Monaco, Colonia, Francoforte o Dresda.</p></div>';
            activateCity('');
            return;
        }

        var spots = city.spots.filter(function (spot) {
            return activeCategory === 'all' || spot.type === activeCategory;
        });

        heading.textContent = 'Nuovi luoghi a ' + city.italianName;
        copy.textContent = city.summary;
        activateCity(city.slug);

        var places = spots.map(function (spot) {
            return '<article class="place-card">' +
                '<div class="place-photo">' +
                    '<span class="place-label">' + escapeHtml(spot.type) + '</span>' +
                    '<span class="time-badge">' + escapeHtml(spot.time) + '</span>' +
                '</div>' +
                '<h3>' + escapeHtml(spot.name) + '</h3>' +
                '<p>' + escapeHtml(spot.note) + '</p>' +
                '<div class="place-foot">Zona: ' + escapeHtml(spot.area) + '</div>' +
            '</article>';
        }).join('');

        if (!places) {
            places = '<div class="empty-state"><strong>Nessun risultato in questo filtro.</strong><p>Scegli un’altra categoria o torna a Nuovi luoghi.</p></div>';
        }

        var route = city.route.map(function (item) {
            return '<article class="route-card">' +
                '<span>' + escapeHtml(item.step) + '</span>' +
                '<div>' +
                    '<h4>' + escapeHtml(item.title) + '</h4>' +
                    '<p>' + escapeHtml(item.detail) + '</p>' +
                '</div>' +
            '</article>';
        }).join('');

        result.innerHTML = '<div class="city-summary">' +
            '<div>' +
                '<h3>' + escapeHtml(city.headline) + '</h3>' +
                '<p>' + escapeHtml(city.bestFor) + '</p>' +
            '</div>' +
            '<span class="summary-badge">' + escapeHtml(city.duration) + '</span>' +
        '</div>' +
        '<div class="places-grid">' + places + '</div>' +
        '<section class="route-panel" aria-label="Itinerario suggerito">' +
            '<h3>Una giornata tipo</h3>' +
            '<div class="route-list">' + route + '</div>' +
        '</section>';

        window.history.replaceState({}, '', '?city=' + encodeURIComponent(city.slug));
    }

    function searchFrom(value, scroll) {
        var city = findCity(value);
        input.value = city ? city.italianName : value;
        if (miniInput) {
            miniInput.value = city ? city.italianName : value;
        }
        renderCity(city);

        if (scroll) {
            document.getElementById('places').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function handleSearch(event, sourceInput) {
        event.preventDefault();
        searchFrom(sourceInput.value, true);
    }

    function bindCategoryFilters() {
        categoryButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                categoryButtons.forEach(function (node) {
                    node.classList.remove('is-active');
                });
                button.classList.add('is-active');
                activeCategory = button.getAttribute('data-category-filter');
                renderCity(findCity(input.value) || cities[0]);
            });
        });
    }

    function initialize() {
        if (!cities.length) {
            return;
        }

        renderOptions();
        renderCityStrip();
        bindCategoryFilters();

        var params = new URLSearchParams(window.location.search);
        var initialCity = findCity(params.get('city')) || cities[0];
        input.value = initialCity.italianName;
        if (miniInput) {
            miniInput.value = initialCity.italianName;
        }
        renderCity(initialCity);
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
