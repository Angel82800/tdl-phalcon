<div class="pad-container" id="contact-section">
    <div class="row" style="min-height:400px;" data-equalizer data-equalize-on="medium">
        <div class="small-12 medium-6 columns" data-equalizer-watch>
            <p class="lead">
                <strong>Try Todyl Today</strong><br>{{leadSentence}}
            </p>
            <h2>{{bigHeadline}}</h2>
            <ul>
                <li>{{bullet1}}</li>
                <li>{{bullet2}}</li>
                <li>{{bullet3}}</li>
            </ul>
            <p>
                <small>{{depositText}}</small>
            </p>

        </div>
        <div class="medium-6 medium-centered columns text-left" id="contactBox" style="position: relative;" data-equalizer-watch>

            {{ form('class': 'form-contact') }}
                <tr>
                    <td>
                        {{ form.render('name') }}
                    </td>
                </tr>
                <tr>
                    <td>
                        {{ form.render('email') }}
                    </td>
                </tr>
                <tr>
                    <td>
                        {{ form.render('businessSize') }}
                    </td>
                </tr>
                <tr>
                    <td>
                       <div class="g-recaptcha" data-sitekey="{{ this.config.recaptcha.publicKey }}"></div>
                    </td>
                </tr>
                <tr>
                    <td align="right"></td>
                    <td>{{ form.render('Submit') }}</td>
                </tr>

                {{ form.render('csrf', ['value': security.getSessionToken()]) }}

            </form>
            <img src="/img/loading.svg" class="icon-loading" alt="loading" id="contact-loading" style="display: none;"/>

        </div>
    </div>
</div>
