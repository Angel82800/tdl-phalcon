<div class="panels_container support-container">
    <div class="row expanded">
        <div class="small-12 columns">
            <div class="panel text-mid-grey">
                <h4 class="kill-margin text-dark">Contact Support</h4>

                <h5 class="kill-margin pt-1">Please contact us at <a href="mailto:support@todyl.com">support@todyl.com</a></h5>
                <h5 class="">
                    or call us at 1-(646)-600-8232, and press option 1.<br>
                    Monday-Friday, 9:30AM-6PM
                </h5>
            </div>
        </div>
    </div>

    <div class="row expanded">
        <div class="small-12 columns">
            <div class="panel">
                <h4 class="kill-margin">Guides and Frequently Asked Questions</h4>

                {% if is_admin %}
                <div class="panel_action_container">
                    <a id="btn_add_topic" class="button"><i class="i-plus icon-xl"></i></a>
                </div>
                {% endif %}
            </div>
        </div>
    </div>

    {% for topic in topics['active'] %}
    <div class="row expanded">
        <div class="small-12 columns">
            <div class="panel">
                <div class="row expanded icon-element" data-equalizer="topic_{{ topic.pk_id }}">
                    <div class="small-12 columns flex-center-vertically pl-0" data-equalizer-watch="topic_{{ topic.pk_id }}">
                        <div class="element">
                            <p class="header"><a href="/support/topic/{{ topic.pk_id }}">{{ topic.name }}</a></p>
                            <p class="sub_header">{{ topic.articles.count() }} {{ topic.articles.count() > 1 ? 'Articles' : 'Article' }}</p>
                        </div>
                    </div>
                </div>

                {% if is_admin %}
                <div class="panel_action_container">
                    <a class="btn_edit_topic button" data-topic-id="{{ topic.pk_id }}" data-topic-name="{{ topic.name }}">
                        <i class="i-edit icon-xl"></i>
                    </a><a class="btn_hide_topic button" data-topic-id="{{ topic.pk_id }}" data-topic-name="{{ topic.name }}">
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
                <h4 class="kill-margin">Hidden Guides and FAQs <span class="text-light-grey">Todyl Admins Only</span></h4>
            </div>
        </div>
    </div>

    {% for topic in topics['hidden'] %}
    <div class="row expanded">
        <div class="small-12 columns">
            <div class="panel">
                <div class="row expanded icon-element" data-equalizer="topic_{{ topic.pk_id }}">
                    <div class="small-12 columns flex-center-vertically pl-0" data-equalizer-watch="topic_{{ topic.pk_id }}">
                        <div class="element">
                            <p class="header">{{ topic.name }}</p>
                            <p class="sub_header">{{ topic.articles.count() }} Articles</p>
                        </div>
                    </div>
                </div>

                <div class="panel_action_container">
                    <a class="btn_edit_topic button" data-topic-id="{{ topic.pk_id }}" data-topic-name="{{ topic.name }}">
                        <i class="i-edit icon-xl"></i>
                    </a><a class="btn_show_topic button" data-topic-id="{{ topic.pk_id }}" data-topic-name="{{ topic.name }}">
                        <i class="i-invisible icon-xl"></i>
                    </a><a class="btn_delete_topic button" data-topic-id="{{ topic.pk_id }}" data-topic-name="{{ topic.name }}">
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
<div class="reveal large_reveal" id="dlg_edit_topic" data-reveal>
    <div class="small-12 columns">
        <h4></h4>
        <button class="close-button" data-close aria-label="Close modal" type="button">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <form id="frm_edit_topic" type="post" enctype="multipart/form-data" data-abide novalidate>
        <input type="hidden" name="action" />
        <input type="hidden" name="id" />

        <div class="small-12 columns">
            <input type="text" name="name" placeholder="Enter a title" required />
            <span class="form-error" data-force-msg="Please enter topic title."></span>
        </div>

        <div class="small-12 columns">
            <label for="topic_icon" class="btn_topic_button">Upload Icon</label>
            <input type="file" id="topic_icon" name="icon" />
            <span class="form-error" data-force-msg="Please upload topic icon."></span>
        </div>
    </form>

    <div class="row">
        <div class="small-12 medium-3 medium-offset-6 columns">
            <a class="button hollow deactive btn-wide" data-close>Cancel</a>
        </div>

        <div class="small-12 medium-3 columns end">
            <a class="button btn-wide" href="javascript:void(0);" id="btn_confirm_topic"></a>
        </div>
    </div>
</div>

<div class="reveal large_reveal" id="dlg_manage_topic" data-reveal>
    <input type="hidden" id="manage_topic_action" />
    <input type="hidden" id="manage_topic_id" />

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
            <a class="button btn-wide" href="javascript:void(0);" id="btn_manage_topic"></a>
        </div>
    </div>
</div>
{% endif %}
