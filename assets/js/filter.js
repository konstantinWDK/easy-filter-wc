jQuery(document).ready(function($) {    
    
    var filterForm = $('#easy-filter-form');
    var originalProducts = null;
    
    function showLoading() {
        // Preserve filter state before any updates
        preserveFilterState();
        
        // Add subtle loading transition to products container
        $('#easy-products-container, .products, .woocommerce ul.products').css({
            'opacity': '1',
            'transition': 'opacity 0.3s ease'
        });
    }
    
    function hideLoading() {
        // Restore full opacity with smooth transition
        $('#easy-products-container, .products, .woocommerce ul.products').css({
            'opacity': '1',
            'transition': 'opacity 0.4s ease'
        });
        
        // Remove inline styles after transition
        setTimeout(function() {
            $('#easy-products-container, .products, .woocommerce ul.products').removeAttr('style');
        }, 400);
        
        // Restore filter state after loading is complete
        restoreFilterState();
    }
    
    function updateProducts(data) {
        // Easy Products custom container (highest priority)
        var customContainer = $('#easy-products-container .products');
        
        // Divi Woo Products module selectors
        var diviProductsContainer = $('.et_pb_wc_upsells .products, .et_pb_wc_related_products .products, .et_pb_shop .products, .et_pb_wc_products .products');
        
        // Standard WooCommerce selectors
        var standardContainer = $('.woocommerce ul.products, .products, .shop-loop-container');
        
        // Try custom container first, then Divi, then standard
        var productsContainer;
        if (customContainer.length > 0) {
            productsContainer = customContainer;
        } else if (diviProductsContainer.length > 0) {
            productsContainer = diviProductsContainer;
        } else {
            productsContainer = standardContainer;
        }
        
        // Fallback to content area
        if (productsContainer.length === 0) {
            productsContainer = $('.woocommerce .content-area, .woocommerce-page .content-area, .et_pb_section .et_pb_row');
        }
        
        if (productsContainer.length > 0) {
            var targetContainer = productsContainer.first();
            
            // Handle custom products container specially with smooth transition
            if (customContainer.length > 0) {
                targetContainer = customContainer;
                targetContainer.fadeOut(200, function() {
                    targetContainer.html(data.products).fadeIn(300);
                });
            } else {
                // For Divi, we might need to update the parent container
                if (targetContainer.hasClass('products') || targetContainer.find('.products').length > 0) {
                    if (targetContainer.find('.products').length > 0) {
                        targetContainer = targetContainer.find('.products').first();
                    }
                }
                targetContainer.fadeOut(200, function() {
                    targetContainer.html(data.products).fadeIn(300);
                });
            }
            
            // Update result count if exists
            var resultCount = $('.woocommerce-result-count');
            if (resultCount.length > 0) {
                resultCount.html(data.result_count_html);
            }
            
            // Update custom products wrapper if exists
            if ($('#easy-products-container').length > 0 && data.result_count_html) {
                $('#easy-products-container .woocommerce-result-count').html(data.result_count_html);
            }
            
            // Update pagination if exists
            if (data.pagination) {
                var paginationContainer = $('.woocommerce-pagination');
                if (paginationContainer.length > 0) {
                    paginationContainer.html($(data.pagination).html());
                } else {
                    // Add pagination after products if it doesn't exist
                    targetContainer.after(data.pagination);
                }
            } else {
                // Remove pagination if no pages
                $('.woocommerce-pagination').remove();
            }
            
            // Log filter results for debugging
            console.log('Easy Filter WC: Filter applied successfully');
            console.log('Easy Filter WC: Found ' + data.found_posts + ' products');
            console.log('Easy Filter WC: Using AND logic for multiple selections');
            if (data.debug && data.debug.filter_logic) {
                console.log('Easy Filter WC: Categories selected:', data.debug.filter_logic.categories_count);
                console.log('Easy Filter WC: Tags selected:', data.debug.filter_logic.tags_count);
                console.log('Easy Filter WC: Attributes selected:', data.debug.filter_logic.attributes_count);
                console.log('Easy Filter WC: Price filter active:', data.debug.filter_logic.has_price_filter);
            }
            
            // Trigger Divi events for proper rendering
            if (window.et_pb_custom && window.et_pb_custom.fix_video_limit) {
                window.et_pb_custom.fix_video_limit();
            }
            
            // Trigger general event
            $('body').trigger('easyfilter_products_updated', [data]);
            
            // Update URL with filter parameters
            updateURL();
            
            // Optional gentle scroll to products (removed aggressive scrolling)
            // Only scroll if user is far from the products area
            if ($(window).scrollTop() > targetContainer.offset().top + 300) {
                $('html, body').animate({
                    scrollTop: targetContainer.offset().top - 50
                }, 300);
            }
            
            // Re-initialize any Divi animations or scripts
            if (typeof window.et_reinit_waypoint_modules === 'function') {
                window.et_reinit_waypoint_modules();
            }
        } else {
            console.log('Easy Filter WC: No products container found.');
            console.log('Custom container:', $('#easy-products-container .products'));
            console.log('Available Divi containers:', $('.et_pb_wc_products, .et_pb_shop, .et_pb_wc_upsells, .et_pb_wc_related_products'));
            console.log('Available standard containers:', $('.products, .woocommerce ul.products'));
            console.log('Easy products wrapper:', $('#easy-products-container'));
        }
    }
    
    function updateURL() {
        var url = new URL(window.location);
        var params = new URLSearchParams();
        
        // Get selected categories
        var categories = [];
        filterForm.find('input[name="categories[]"]:checked').each(function() {
            categories.push($(this).val());
        });
        if (categories.length > 0) {
            params.set('filter_categories', categories.join(','));
        }
        
        // Get selected tags
        var tags = [];
        filterForm.find('input[name="tags[]"]:checked').each(function() {
            tags.push($(this).val());
        });
        if (tags.length > 0) {
            params.set('filter_tags', tags.join(','));
        }
        
        // Get price range
        var minPrice = $('#min-price').val();
        var maxPrice = $('#max-price').val();
        if (minPrice && minPrice > 0) {
            params.set('filter_min_price', minPrice);
        }
        if (maxPrice && maxPrice > 0) {
            params.set('filter_max_price', maxPrice);
        }
        
        // Get selected attributes
        var attributes = {};
        filterForm.find('input[name^="attributes["]:checked').each(function() {
            var name = $(this).attr('name');
            var matches = name.match(/attributes\[([^\]]+)\]\[\]/);
            if (matches) {
                var taxonomy = matches[1];
                if (!attributes[taxonomy]) {
                    attributes[taxonomy] = [];
                }
                attributes[taxonomy].push($(this).val());
            }
        });
        
        for (var taxonomy in attributes) {
            if (attributes[taxonomy].length > 0) {
                params.set('filter_' + taxonomy.replace('pa_', 'attr_'), attributes[taxonomy].join(','));
            }
        }
        
        // Remove empty parameters
        var currentParams = new URLSearchParams(url.search);
        var keysToRemove = [];
        for (var key of currentParams.keys()) {
            if (key.startsWith('filter_')) {
                keysToRemove.push(key);
            }
        }
        keysToRemove.forEach(key => currentParams.delete(key));
        
        // Add new filter parameters
        for (var [key, value] of params.entries()) {
            currentParams.set(key, value);
        }
        
        // Update URL without page reload
        var newUrl = url.pathname + (currentParams.toString() ? '?' + currentParams.toString() : '');
        window.history.pushState({}, '', newUrl);
        
        // Update page title to reflect active filters
        updatePageTitle();
        
        console.log('Easy Filter WC: URL updated to:', newUrl);
    }
    
    function updatePageTitle() {
        var baseTitle = document.title.split(' - ')[0]; // Get base title before filters
        var filterParts = [];
        
        // Add category filters to title
        var selectedCategories = [];
        filterForm.find('input[name="categories[]"]:checked').each(function() {
            var label = $(this).closest('label').text().trim();
            var categoryName = label.replace(/\s*\(\d+\)$/, ''); // Remove count
            selectedCategories.push(categoryName);
        });
        if (selectedCategories.length > 0) {
            filterParts.push('Categories: ' + selectedCategories.join(', '));
        }
        
        // Add price filter to title
        var minPrice = $('#min-price').val();
        var maxPrice = $('#max-price').val();
        if (minPrice || maxPrice) {
            var priceText = 'Price: ';
            if (minPrice && maxPrice) {
                priceText += '$' + minPrice + ' - $' + maxPrice;
            } else if (minPrice) {
                priceText += 'From $' + minPrice;
            } else if (maxPrice) {
                priceText += 'Up to $' + maxPrice;
            }
            filterParts.push(priceText);
        }
        
        // Add tag filters to title
        var selectedTags = [];
        filterForm.find('input[name="tags[]"]:checked').each(function() {
            var label = $(this).closest('label').text().trim();
            var tagName = label.replace(/\s*\(\d+\)$/, ''); // Remove count
            selectedTags.push(tagName);
        });
        if (selectedTags.length > 0) {
            filterParts.push('Tags: ' + selectedTags.join(', '));
        }
        
        // Update document title
        var newTitle = baseTitle;
        if (filterParts.length > 0) {
            newTitle += ' - ' + filterParts.join(' | ');
        }
        
        document.title = newTitle;
        console.log('Easy Filter WC: Page title updated to:', newTitle);
    }
    
    function loadFiltersFromURL() {
        var urlParams = new URLSearchParams(window.location.search);
        
        // Load categories
        if (urlParams.has('filter_categories')) {
            var categoryIds = urlParams.get('filter_categories').split(',');
            categoryIds.forEach(function(catId) {
                filterForm.find('input[name="categories[]"][value="' + catId + '"]').prop('checked', true);
            });
        }
        
        // Load tags
        if (urlParams.has('filter_tags')) {
            var tagIds = urlParams.get('filter_tags').split(',');
            tagIds.forEach(function(tagId) {
                filterForm.find('input[name="tags[]"][value="' + tagId + '"]').prop('checked', true);
            });
        }
        
        // Load price range
        if (urlParams.has('filter_min_price')) {
            $('#min-price').val(urlParams.get('filter_min_price'));
        }
        if (urlParams.has('filter_max_price')) {
            $('#max-price').val(urlParams.get('filter_max_price'));
        }
        
        // Update price slider if exists
        if ($('#price-slider').length) {
            var minVal = parseInt($('#min-price').val()) || $('#price-slider').slider('option', 'min');
            var maxVal = parseInt($('#max-price').val()) || $('#price-slider').slider('option', 'max');
            $('#price-slider').slider('values', [minVal, maxVal]);
        }
        
        // Load attributes
        for (var [key, value] of urlParams.entries()) {
            if (key.startsWith('filter_attr_')) {
                var attrName = 'pa_' + key.replace('filter_attr_', '');
                var attrIds = value.split(',');
                attrIds.forEach(function(attrId) {
                    filterForm.find('input[name="attributes[' + attrName + '][]"][value="' + attrId + '"]').prop('checked', true);
                });
            }
        }
        
        // Apply filters if any parameters exist
        var hasFilters = false;
        for (var [key, value] of urlParams.entries()) {
            if (key.startsWith('filter_')) {
                hasFilters = true;
                break;
            }
        }
        
        if (hasFilters) {
            console.log('Easy Filter WC: Loading filters from URL');
            setTimeout(function() {
                performFilter();
            }, 100);
        }
    }
    
    function performFilter() {
        console.log('Easy Filter WC: performFilter() called');
        
        if (!filterForm.length) {
            console.log('Easy Filter WC: No filter form found!');
            return;
        }
        
        showLoading();
        
        var formData = {
            action: 'easy_filter_products',
            nonce: easy_filter_ajax.nonce,
            categories: [],
            tags: [],
            min_price: $('#min-price').val(),
            max_price: $('#max-price').val(),
            attributes: {},
            current_category: $('input[name="current_category"]').val() || 0
        };
        
        // Check for selected categories
        var categoryCheckboxes = filterForm.find('input[name="categories[]"]');
        var checkedCategories = filterForm.find('input[name="categories[]"]:checked');
        
        console.log('Easy Filter WC: Found', categoryCheckboxes.length, 'category checkboxes total');
        console.log('Easy Filter WC: Found', checkedCategories.length, 'checked category checkboxes');
        
        checkedCategories.each(function() {
            console.log('Easy Filter WC: Selected category ID:', $(this).val(), 'Name:', $(this).data('category-name'));
            formData.categories.push($(this).val());
        });
        
        filterForm.find('input[name="tags[]"]:checked').each(function() {
            formData.tags.push($(this).val());
        });
        
        filterForm.find('input[name^="attributes["]:checked').each(function() {
            var name = $(this).attr('name');
            var matches = name.match(/attributes\[([^\]]+)\]\[\]/);
            if (matches) {
                var taxonomy = matches[1];
                if (!formData.attributes[taxonomy]) {
                    formData.attributes[taxonomy] = [];
                }
                formData.attributes[taxonomy].push($(this).val());
            }
        });
        
        console.log('Easy Filter WC: Sending filter request with data:', formData);
        console.log('Easy Filter WC: Selected categories:', formData.categories);
        console.log('Easy Filter WC: Current category context:', formData.current_category);
        
        $.ajax({
            url: easy_filter_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Easy Filter WC: Received response:', response);
                if (response.success) {
                    console.log('Easy Filter WC: Found', response.data.found_posts, 'products');
                    console.log('Easy Filter WC: Filter logic debug:', response.data.debug.filter_logic);
                    console.log('Easy Filter WC: Final category terms used:', response.data.debug.processed_category_terms);
                    updateProducts(response.data);
                } else {
                    console.error('Filter error:', response);
                }
                hideLoading();
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                console.log('XHR:', xhr.responseText);
                hideLoading();
            }
        });
    }
    
    filterForm.on('submit', function(e) {
        e.preventDefault();
        performFilter();
    });
    
    filterForm.find('input[type="checkbox"]').on('change', function() {
        console.log('Easy Filter WC: Checkbox changed:', $(this).attr('name'), 'checked:', $(this).is(':checked'));
        var autoFilter = true;
        if (autoFilter) {
            setTimeout(performFilter, 150);
        }
    });
    
    $('#min-price, #max-price').on('change blur', function() {
        var autoFilter = true;
        if (autoFilter) {
            setTimeout(performFilter, 500);
        }
    });
    
    if ($('#price-slider').length) {
        var priceSlider = $('#price-slider');
        var minPriceInput = $('#min-price');
        var maxPriceInput = $('#max-price');
        
        minPriceInput.on('input', function() {
            var minVal = parseInt($(this).val()) || priceSlider.slider('option', 'min');
            var maxVal = parseInt(maxPriceInput.val()) || priceSlider.slider('option', 'max');
            priceSlider.slider('values', [minVal, maxVal]);
        });
        
        maxPriceInput.on('input', function() {
            var minVal = parseInt(minPriceInput.val()) || priceSlider.slider('option', 'min');
            var maxVal = parseInt($(this).val()) || priceSlider.slider('option', 'max');
            priceSlider.slider('values', [minVal, maxVal]);
        });
        
        priceSlider.on('slidechange', function(event, ui) {
            setTimeout(performFilter, 200);
        });
    }
    
    $('.filter-reset').on('click', function(e) {
        e.preventDefault();
        
        filterForm.find('input[type="checkbox"]').prop('checked', false);
        filterForm.find('input[type="number"]').val('');
        
        if ($('#price-slider').length) {
            var slider = $('#price-slider');
            slider.slider('values', [
                slider.slider('option', 'min'),
                slider.slider('option', 'max')
            ]);
        }
        
        // Clear URL parameters
        var url = new URL(window.location);
        var currentParams = new URLSearchParams(url.search);
        var keysToRemove = [];
        for (var key of currentParams.keys()) {
            if (key.startsWith('filter_')) {
                keysToRemove.push(key);
            }
        }
        keysToRemove.forEach(key => currentParams.delete(key));
        
        var newUrl = url.pathname + (currentParams.toString() ? '?' + currentParams.toString() : '');
        window.history.pushState({}, '', newUrl);
        
        // Reset page title
        var baseTitle = document.title.split(' - ')[0];
        document.title = baseTitle;
        
        console.log('Easy Filter WC: Filters reset, URL updated to:', newUrl);
        
        performFilter();
    });
    
    // Initialize filters from URL on page load
    loadFiltersFromURL();
    
    // Mobile filter functionality
    var mobileFilterInitialized = false;
    
    function preserveFilterState() {
        // Store which groups are expanded before any updates
        var expandedGroups = $('.filter-group.expanded');
        expandedGroups.addClass('preserve-expanded');
        console.log('Easy Filter WC: Preserving state for', expandedGroups.length, 'expanded groups');
    }
    
    function restoreFilterState() {
        // Restore expanded state after updates
        var preservedGroups = $('.filter-group.preserve-expanded');
        preservedGroups.addClass('expanded user-expanded').removeClass('preserve-expanded');
        console.log('Easy Filter WC: Restoring state for', preservedGroups.length, 'preserved groups');
    }
    
    function initMobileFilter() {
        console.log('Easy Filter WC: initMobileFilter() called, window width:', $(window).width());
        var filterWidget = $('.easy-filter-widget');
        var filterTitle = filterWidget.find('.filter-title');
        var filterGroups = filterWidget.find('.filter-group');
        
        // Only apply mobile functionality on mobile devices
        if ($(window).width() <= 768) {
            console.log('Easy Filter WC: Mobile mode active, initialized:', mobileFilterInitialized);
            // Only initialize once, preserve state on subsequent calls
            if (!mobileFilterInitialized) {
                // Start with filter collapsed on mobile
                filterWidget.addClass('collapsed');
            }
            
            // Wrap filter group content for collapsing - only if not already wrapped
            filterGroups.each(function() {
                var $group = $(this);
                if (!$group.find('.filter-content').length) {
                    var $content = $group.find('label, .price-inputs, .attribute-group, #price-slider');
                    if ($content.length > 0) {
                        $content.wrapAll('<div class="filter-content"></div>');
                    }
                }
                // Only collapse groups if they haven't been manually expanded AND this is first init
                if (!$group.hasClass('user-expanded') && !mobileFilterInitialized) {
                    $group.removeClass('expanded');
                    console.log('Easy Filter WC: Collapsing group (first init):', $group.find('h4').text());
                } else if ($group.hasClass('user-expanded')) {
                    console.log('Easy Filter WC: Preserving user-expanded group:', $group.find('h4').text());
                }
            });
            
            // Mark as initialized after wrapping
            if (!mobileFilterInitialized) {
                mobileFilterInitialized = true;
            }
            
            // Toggle main filter
            filterTitle.off('click.mobile').on('click.mobile', function(e) {
                e.preventDefault();
                filterWidget.toggleClass('collapsed');
            });
            
            // Toggle individual filter groups
            filterGroups.find('h4').off('click.mobile').on('click.mobile', function(e) {
                e.preventDefault();
                var $group = $(this).closest('.filter-group');
                var wasExpanded = $group.hasClass('expanded');
                $group.toggleClass('expanded');
                
                // Mark as user-expanded to preserve state
                if ($group.hasClass('expanded')) {
                    $group.addClass('user-expanded');
                    console.log('Easy Filter WC: Group expanded by user:', $group.find('h4').text());
                } else {
                    $group.removeClass('user-expanded');
                    console.log('Easy Filter WC: Group collapsed by user:', $group.find('h4').text());
                }
                
                // Auto-expand main filter if a group is clicked
                if ($group.hasClass('expanded') && filterWidget.hasClass('collapsed')) {
                    filterWidget.removeClass('collapsed');
                }
            });
            
            // Prevent filter group collapse when clicking on content inside
            filterGroups.find('.filter-content').off('click.mobile').on('click.mobile', function(e) {
                e.stopPropagation();
            });
        } else {
            // Remove mobile classes on desktop
            filterWidget.removeClass('collapsed');
            filterGroups.removeClass('expanded user-expanded');
            filterTitle.off('click.mobile');
            filterGroups.find('h4').off('click.mobile');
            filterGroups.find('.filter-content').off('click.mobile');
            mobileFilterInitialized = false;
        }
    }
    
    // Initialize mobile filter on page load
    initMobileFilter();
    
    // Debug function to check mobile filter state
    window.debugMobileFilter = function() {
        console.log('=== Mobile Filter Debug ===');
        console.log('Window width:', $(window).width());
        console.log('Mobile initialized:', mobileFilterInitialized);
        console.log('Filter widget:', $('.easy-filter-widget').length);
        console.log('Filter widget classes:', $('.easy-filter-widget').attr('class'));
        console.log('Filter groups:', $('.filter-group').length);
        $('.filter-group').each(function(i) {
            var $group = $(this);
            console.log('Group ' + i + ':', $group.find('h4').text(), 'Classes:', $group.attr('class'));
            console.log('  - Has filter-content:', $group.find('.filter-content').length > 0);
            console.log('  - Is expanded:', $group.hasClass('expanded'));
            console.log('  - Is user-expanded:', $group.hasClass('user-expanded'));
        });
        console.log('========================');
    };
    
    // Reinitialize on window resize
    $(window).on('resize', function() {
        clearTimeout(window.resizeTimeout);
        window.resizeTimeout = setTimeout(function() {
            console.log('Easy Filter WC: Window resized, reinitializing mobile filter');
            initMobileFilter();
        }, 250);
    });
    
    
    // Handle browser back/forward navigation
    $(window).on('popstate', function(event) {
        console.log('Easy Filter WC: Browser navigation detected, reloading filters');
        
        // Clear current selections
        filterForm.find('input[type="checkbox"]').prop('checked', false);
        filterForm.find('input[type="number"]').val('');
        
        // Reset price slider
        if ($('#price-slider').length) {
            var slider = $('#price-slider');
            slider.slider('values', [
                slider.slider('option', 'min'),
                slider.slider('option', 'max')
            ]);
        }
        
        // Load new filters from URL
        loadFiltersFromURL();
    });
    
    $('body').on('click', '.woocommerce-pagination a', function(e) {
        e.preventDefault();
        
        var href = $(this).attr('href');
        var url = new URL(href);
        var page = url.searchParams.get('paged') || 1;
        
        showLoading();
        
        var formData = {
            action: 'easy_filter_products',
            nonce: easy_filter_ajax.nonce,
            paged: page,
            categories: [],
            tags: [],
            min_price: $('#min-price').val(),
            max_price: $('#max-price').val(),
            attributes: {},
            current_category: $('input[name="current_category"]').val() || 0
        };
        
        filterForm.find('input[name="categories[]"]:checked').each(function() {
            formData.categories.push($(this).val());
        });
        
        filterForm.find('input[name="tags[]"]:checked').each(function() {
            formData.tags.push($(this).val());
        });
        
        filterForm.find('input[name^="attributes["]:checked').each(function() {
            var name = $(this).attr('name');
            var matches = name.match(/attributes\[([^\]]+)\]\[\]/);
            if (matches) {
                var taxonomy = matches[1];
                if (!formData.attributes[taxonomy]) {
                    formData.attributes[taxonomy] = [];
                }
                formData.attributes[taxonomy].push($(this).val());
            }
        });
        
        $.ajax({
            url: easy_filter_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    updateProducts(response.data);
                    window.history.pushState({}, '', href);
                }
                hideLoading();
            },
            error: function() {
                hideLoading();
            }
        });
    });
    
    $(document).on('keypress', '#min-price, #max-price', function(e) {
        if (e.which === 13) {
            performFilter();
        }
    });
    
    var filterTimeout;
    filterForm.find('input').on('input change', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function() {
            if ($(this).is(':checkbox') && $(this).is(':checked')) {
                performFilter();
            }
        }.bind(this), 300);
    });
    
});