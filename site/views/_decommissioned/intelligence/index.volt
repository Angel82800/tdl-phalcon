<div class="pricing-hero">
    <div class="row">
        <div class="row" style="margin-top:100px;">
            <div class="small-8 small-centered text-center text-white columns">
                <h3>{{ title }}</h3>
            </div>
        </div>
        <div class="row">
            <div class="small-2 small-centered short-divider columns">

            </div>
        </div>
        <div class="row">
            <div class="small-8 small-centered text-center text-white columns">
                <h4>
                    {{ subtitle }}
                </h4>
            </div>
        </div>
    </div>
</div>
<div class="row article-border">
    <div class="medium-8 columns">
        <div class="todyl-title-color"><h3>{{ title }}</h3></div>
        <div class="article-content">{{ content }}</div>
    </div>
    <div class="medium-4 columns">
        <div class="row">
            <div class="medium-12 columns" style="margin-bottom: 30px;">
                <div class="todyl-title-color"><h3>Similar Questions</h3></div>
                {% for question in similarQuestions %}
                    <div class="similar-question">{{ question }}</div>
                {% endfor %}
            </div>
            <div class="medium-12 columns">
                <div class="todyl-title-color" style="margin-bottom:20px;"><h3>About Todyl</h3></div>
                <div>Todyl is an all-in-one service that is simple to install and offers the strongest, most up-to-date levels of protection - at a fraction of the cost.
                </div>
            </div>
        </div>
    </div>
</div>
<div class="pad-20">
</div>

{{ partial('index/partials/contact') }}
