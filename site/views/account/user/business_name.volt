<div class="medium-4 columns">
    <p class="title">{{ organization.name ? 'Member Of' : 'Account Owner' }}</p>
</div>

<div class="medium-8 columns end">
    <div class="static-field">{{ organization.name ? organization.name : org_owner.email }}</div>
</div>
