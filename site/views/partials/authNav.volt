<div class="dashboard-vertical-menu full-height hide-for-small-only">
{% if navigation_items is not empty %}
    <ul class="vertical menu" data-responsive-menu="drilldown medium-dropdown">
        {% for menu in navigation_items %}
            {% if menu['email'] is empty or identity['email'] != menu['email'] %}
                {% if menu['no_ftu'] is not empty and session.get('is_ftu') %}
                    {% continue %}
                {% endif %}

                {% if (menu['for_admin'] is not empty and ! is_admin) or (menu['for_org_admin'] is not empty and identity['role'] is 'user') or (menu['show_suspended'] is empty and ! identity['is_active']) %}
                    {% continue %}
                {% endif %}
            {% endif %}

            {% if (dispatcher.getControllerName() == menu['controller'] and (menu['action'] is empty or dispatcher.getActionName() == menu['action'])) or (menu['second_controller'] is not empty and dispatcher.getControllerName() == menu['second_controller']) %}
                <li class="active">
            {% else %}
                <li>
            {% endif %}

                <a class="clearfix" href="{{ menu['link'] }}">
                    <i class="{{ menu['icon'] }} icon-xxl"></i> <p>{{ identity['role'] is 'user' and menu['title_user'] is not empty ? menu['title_user'] : menu['title'] }}</p>
                </a>
            </li>
        {% endfor %}
    </ul>
{% endif %}
</div>

<!--OFF CANVAS AUTH NAV -->

<div class="row expanded jumbo-nav off-canvas position-right" id="offCanvasRightAuth" data-off-canvas>

    <div class="row expanded miniAuthNav">
        <div class="small-6 columns">
         <a href="/" style="border:none;outline:none;">
            <div class="logo">
                &nbsp;
            </div>
         </a>
        </div>
        <div class="small-6 text-right columns pt-1">
            <a href="#">
                <i class="i-close-solo text-white text-2em" data-close="offCanvasRightAuth"></i>
            </a>
        </div>
    </div>



    <div class="dashboard-vertical-menu">
    {% if navigation_items is not empty %}
        <ul class="vertical menu">
            {% for menu in navigation_items %}
                {% if menu['email'] is empty or identity['email'] != menu['email'] %}
                    {% if menu['no_ftu'] is not empty and session.get('is_ftu') %}
                        {% continue %}
                    {% endif %}

                    {% if (menu['for_admin'] is not empty and ! is_admin) or (menu['for_org_admin'] is not empty and identity['role'] is 'user') or (menu['show_suspended'] is empty and ! identity['is_active']) %}
                        {% continue %}
                    {% endif %}
                {% endif %}

                {% if dispatcher.getControllerName() == menu['controller'] and (menu['action'] is empty or dispatcher.getActionName() == menu['action']) %}
                    <li class="active">
                {% else %}
                    <li>
                {% endif %}
                    <a class="clearfix" href="{{ menu['link'] }}">
                        <i class="{{ menu['icon'] }} icon-xxl"></i> <p>{{ identity['role'] is 'user' and menu['title_user'] is not empty ? menu['title_user'] : menu['title'] }}</p>
                    </a>
                </li>
            {% endfor %}
            <li>
                <a href="/account"><i class="i-user icon-xxl"></i> Your Account</a>
            </li>
            <li>
                <a href="/session/logout"><i class="i-lock icon-xxl"></i> Log Out</a>
            </li>
        </ul>
    {% endif %}
    </div>
</div>

<!--END OFF CANVAS AUTH NAV -->
