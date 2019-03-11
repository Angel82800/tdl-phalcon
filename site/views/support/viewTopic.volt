<div class="panels_container support-container">
    <div class="row expanded">
        <div class="small-12 columns">
            <div class="panel">
                <h4 class="kill-margin">Articles in Topic: {{ topic.name }}</h4>

                {% if is_admin %}
                <div class="panel_action_container">
                    <a href="/support/add/{{ topic.pk_id }}" class="button"><i class="i-plus icon-xl"></i></a>
                </div>
                {% endif %}
            </div>
        </div>
    </div>

    {% for article in articles['active'] %}
    <div class="row expanded">
        <div class="small-12 columns">
            <div class="panel article_title">
                <div class="row expanded">
                    <div class="small-12 columns flex-center-vertically">
                        <h4><a href="/support/view/{{ article.pk_id }}">{{ article.title }}</a></h4>
                    </div>
                </div>

                {% if is_admin %}
                <div class="panel_action_container">
                    <a class="button" href="/support/edit/{{ article.pk_id }}">
                        <i class="i-edit icon-xl"></i>
                    </a><a class="btn_hide_article button" data-article-id="{{ article.pk_id }}" data-article-name="{{ article.title }}">
                        <i class="i-visible icon-xl"></i>
                    </a>
                </div>
                {% endif %}
            </div>
        </div>
    </div>
    {% endfor %}

    {% if is_admin %}
    <div class="row expanded">
        <div class="small-12 columns">
            <div class="panel">
                <h4 class="kill-margin">Hidden Articles <span class="text-light-grey">Todyl Admins Only</span></h4>
            </div>
        </div>
    </div>

    {% for article in articles['hidden'] %}
    <div class="row expanded">
        <div class="small-12 columns">
            <div class="panel">
                <div class="row expanded icon-element" data-equalizer="article_{{ article.pk_id }}">
                    <h4 class="kill-margin">{{ article.title }}</h4>
                </div>

                <div class="panel_action_container">
                    <a class="btn_show_article button" data-article-id="{{ article.pk_id }}" data-article-name="{{ article.title }}">
                        <i class="i-invisible icon-xl"></i>
                    </a><a class="btn_delete_article button" data-article-id="{{ article.pk_id }}" data-article-name="{{ article.title }}">
                        <i class="i-trash icon-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    {% endfor %}

    {% endif %}

</div>

{% if is_admin %}

<div class="reveal article_dlg" id="dlg_manage_article" data-reveal>
    <input type="hidden" id="manage_article_action" />
    <input type="hidden" id="manage_article_id" />

    <div class="small-12 columns">
        <h4></h4>
        <button class="close-button" data-close aria-label="Close modal" type="button">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <div class="row">
        <div class="small-12 columns">
            <p class="desc"></p>
        </div>
    </div>

    <div class="row">
        <div class="small-12 medium-4 medium-offset-4 columns">
            <a class="button hollow deactive btn-wide" data-close>Cancel</a>
        </div>

        <div class="small-12 medium-4 columns end">
            <a class="button btn-wide" href="javascript:void(0);" id="btn_manage_article"></a>
        </div>
    </div>
</div>

{% endif %}
