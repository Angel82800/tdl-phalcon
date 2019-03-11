{{ partial('partials/authTopBar') }}

<div id="dashboard-content" class="row expanded container" data-equalizer="container" data-equalize-on="medium">
    <div class="private-sidebar" data-equalizer-watch="container">
        {{ partial('partials/authNav') }}
    </div>

    <div class="private-content" data-equalizer-watch="container">
		{{ partial('partials/loading_spinner') }}

        <div class="private-inner">
            <div class="flash-container">
                {{ partial('partials/flash') }}
            </div>

            {{ content() }}
        </div>
    </div>

</div>

{{ partial('partials/footer') }}
