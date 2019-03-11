<div class="settings-section">
    <h4 style="color: #3988BF">Private Wireless Network</h4>
    <p> Users will create their own passwords to connect to this secure network. <a href="/users">Manage Users.</a></p>

    {{ form('class': 'settings-page-form', 'id': 'private-network-form') }}

        {{privateNetworkForm.render('formType')}}
        <div class="wireless-network-info row collapse">
            <div class="small-12 columns">
                {% if privateNetworkMessage is not empty %}
                    {% if privateNetworkSucceeded %}
                        <div class="callout success" data-closable>
                            <span class="lead">{{privateNetworkMessage}}</span>
                            <button class="close-button" aria-label="Dismiss alert" type="button" data-close>
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    {% else %}
                        <div class="callout alert" data-closable>
                            <span class="lead">{{privateNetworkMessage}}</span>
                            <button class="close-button" aria-label="Dismiss alert" type="button" data-close>
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    {% endif %}
                {% endif %}
            </div>

            <div class="medium-6 columns">
                {{privateNetworkForm.label('private-network-name')}}
            </div>

            <div class="medium-4 columns">
                {{privateNetworkForm.render('private-network-name')}}
            </div>
            <div class="medium-2 columns">
                <span class="float-right" id="private-network-change"></span>
            </div>
            <div class="medium-6 columns password-confirmation">
                {{privateNetworkForm.label('password')}}
            </div>
            <div class="medium-4 columns password-confirmation">
                {{privateNetworkForm.render('password')}}
            </div>
            <div class="medium-4 medium-push-6 columns medium-push-6 end password-confirmation">
                {{privateNetworkForm.render('submit')}}
            </div>
        </div>
    </form>
</div>
