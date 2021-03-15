<script src="{{ asset('frontend/js/typeahead.jquery.js') }}"></script>
<script src="{{ asset('frontend/js/bloodhound.js') }}"></script>

<script>
    $(document).ready(function(){

        var nbaTeams = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.whitespace,
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            local: [],
        });

        var nhlTeams = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.whitespace,
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            local: <?php echo $search_cities_json; ?>
        });

        $('#multiple-datasets .typeahead').typeahead({
                minLength: 3,
                highlight: true
            },
            {
                name: 'nba-teams',
                source: nbaTeams,
                templates: {
                    header: '<h5 class="league-name">State</h5>'
                },
                limit: 30,
            },
            {
                name: 'nhl-teams',
                source: nhlTeams,
                templates: {
                    header: '<h5 class="league-name">City</h5>'
                },
                limit: 100,
            });

    });

</script>
