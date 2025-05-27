<script>
    function setupSelect2(elementId, routeName, options = {}, processItem = null) {
        $('#' + elementId).select2({
            placeholder: options.placeholder || 'Select an option',
            allowClear: options.allowClear !== undefined ? options.allowClear : true,
            width: options.width || '100%',
            ajax: {
                url: routeName,
                dataType: 'json',
                delay: options.delay || 250,
                processResults: function (data) {
                    return {
                            results: $.map(data, function(item) {
                                return {
                                    text: item.text,
                                    id: item.id
                                };
                            })
                        };
                },
                cache: options.cache !== undefined ? options.cache : true
            }
        });
    }

    function initializeSelect2() {
        setupSelect2('agent_ids', "{{ route('admin.agent.search') }}",
        {
            placeholder: 'Select an Agent',
            allowClear: true,
        },
        function(item) {
            return { text: item.text, id: item.id };
        }
        );

        setupSelect2('state_ids', "{{ route('admin.state.search') }}",
            { placeholder: 'Select a State', allowClear: true },
            function(item) {
                return { text: item.text, id: item.id }; // Fix item key
            }
        );

        setupSelect2('campaign_ids', "{{ route('admin.campaign.search') }}",
            { placeholder: 'Select a Campaign', allowClear: true, delay: 300 },
            function(item) {
                return { text: item.text, id: item.id }; // Fix item key
            }
        );
    }
</script>
