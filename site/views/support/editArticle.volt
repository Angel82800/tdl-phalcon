<link rel="stylesheet" type="text/css" href="/lib/contenttools/content-tools.min.css">

<div class="panels_container support-container">
    <div class="row expanded">
        <div class="small-12 columns">
            <div class="panel">
            	<div class="breadcrumb">
            		{% if type == 'new' %}
            			<a href="/support">Support</a> / New Article
            		{% else %}
            			<a href="/support">Support</a> / <a href="/support/topic/{{ article.topic.pk_id }}">{{ article.topic.name }}</a> / {{ article.title }}
            		{% endif %}
            	</div>
            </div>
        </div>
    </div>

    <div class="row expanded">
        <div class="small-12 columns">
            <div class="panel clearfix">
                {% if type == 'new' %}
				    {{ partial("support/template/newArticle") }}
                {% else %}
                    <h4 data-fixture data-name="article-title">{{ article.title }}</h4>

                    <div data-editable data-name="article-content">{{ article.content }}</div>
                {% endif %}

                <!-- <div class="panel_action_container">
                    <a id="btn_ct_init" class="button" href="javascript:void(0);">
                        <i class="icon i-edit icon-xl"></i>
                    </a>
                </div> -->
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
	var edit_type = '{{ type }}';
	var identifier = '{{ identifier }}';
</script>

<script src="/lib/contenttools/content-tools.min.js"></script>
<script src="/lib/contenttools/init.js"></script>
