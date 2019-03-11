<div class="panels_container support-container">
    <div class="row expanded">
        <div class="small-12 columns">
            <div class="panel">
            	<div class="breadcrumb">
        			<a href="/support">Support</a> / <a href="/support/topic/{{ article.topic.pk_id }}">{{ article.topic.name }}</a> / {{ article.title }}
            	</div>
            </div>
        </div>
    </div>

    <div class="row expanded">
        <div class="small-12 columns">
            <div class="panel clearfix">
                <h4>{{ article.title }}</h4>
                <div class="support-style-override">{{ article.content }}</div>
            </div>
        </div>
    </div>
</div>
