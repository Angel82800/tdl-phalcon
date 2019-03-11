<footer class="hide-for-small-only">
<div class="row expanded white-text">
    <div class="small-6 medium-3 large-2 columns pad-tb-2">
        <ul class="list">
        <li>{{ link_to('index', 'Home')}}</li>
        <li><a href="/details">Under the Hood</a></li>
        <li><a href="/pricing">Pricing</a></li>
        <li>{{ link_to('about', 'About Us')}}</li>
        <li>{{ link_to('landing', 'Industry Risks')}}</li>
        <!-- <li>{{ link_to('partners', 'Partner Platform')}}</li>-->
        <li><a href="https://blog.todyl.com" target="_blank">Cybersecurity Blog</a></li>
        <li>{{ link_to('privacy', 'Privacy Policy') }}</li>
        <li>{{ link_to('terms', 'Terms of Service') }}</li>
        </ul>
    </div>
    <div class="small-6 medium-4 large-4 columns pad-tb-2 end">
        <ul class="list">
           <li><a href="https://www.facebook.com/todylprotection/" target="_blank"><i class="i-facebook"></i>&nbsp;&nbsp;Facebook</a></li>
           <li><a href="https://twitter.com/todylprotection" target="_blank"><i class="i-twitter"></i>&nbsp;&nbsp;Twitter</a></li>
           <li><a href="https://www.linkedin.com/company/11264170/" target="_blank"><i class="i-linkedin"></i>&nbsp;&nbsp;LinkedIn</a></li>
           <li><a href="https://calendly.com/todyl/specialist-call" target="_blank"><i class="i-phone"></i>&nbsp;&nbsp;Schedule a Call:</a> 844-311-6900</li>
          <li>&#169; {{ date("Y") }} Todyl, Inc.</li>                
        </ul>
        <a href="https://www.bbb.org/new-york-city/business-reviews/computers-network-security/todyl-inc-in-new-york-ny-169310/#sealclick" target="_blank" rel="nofollow"><img style="margin-left:1.25em; border: 0" src="https://seal-newyork.bbb.org/seals/blue-seal-200-42-whitetxt-bbb-169310.png" alt="Todyl, Inc. BBB Business Review" /></a>
    </div>
</div>
</footer>



<!-- mobile footer -->
<footer class="mobile-footer show-for-small-only">
<div class="row">
    <div class="small-8 columns">
        {{ link_to('terms', 'Terms of Use') }}&nbsp;&nbsp;|&nbsp;&nbsp;
        {{ link_to('privacy', 'Privacy Policy') }}
    </div>

    <div class="small-4 columns end text-right">
        &#169; Todyl {{ date("Y") }}
    </div>
</div>
<div class="row">
  <div class="small-12 columns text-left pt-2">
    <a href="https://www.bbb.org/new-york-city/business-reviews/computers-network-security/todyl-inc-in-new-york-ny-169310/#sealclick" target="_blank" rel="nofollow"><img src="https://seal-newyork.bbb.org/seals/blue-seal-200-42-whitetxt-bbb-169310.png" style="border: 0;" alt="Todyl, Inc. BBB Business Review" /></a>
  </div>
</div>
</footer>

<!-- Start of Async Drift Code -->
<script>
!function() {
  var t;
  if (t = window.driftt = window.drift = window.driftt || [], !t.init) return t.invoked ? void (window.console && console.error && console.error("Drift snippet included twice.")) : (t.invoked = !0,
  t.methods = [ "identify", "config", "track", "reset", "debug", "show", "ping", "page", "hide", "off", "on" ],
  t.factory = function(e) {
    return function() {
      var n;
      return n = Array.prototype.slice.call(arguments), n.unshift(e), t.push(n), t;
    };
  }, t.methods.forEach(function(e) {
    t[e] = t.factory(e);
  }), t.load = function(t) {
    var e, n, o, i;
    e = 3e5, i = Math.ceil(new Date() / e) * e, o = document.createElement("script"),
    o.type = "text/javascript", o.async = !0, o.crossorigin = "anonymous", o.src = "https://js.driftt.com/include/" + i + "/" + t + ".js",
    n = document.getElementsByTagName("script")[0], n.parentNode.insertBefore(o, n);
  });
}();
drift.SNIPPET_VERSION = '0.3.1';

// load should come here

</script>
<!-- End of Async Drift Code -->

{% if (not logged_in and router.getControllerName() not in ['session', 'beta', 'registration', 'download'])
      or (router.getControllerName() in ['support']) %}

<script>
  drift.load('han4vxm4ebps');
</script>

{% endif %}

<!-- Start Tracking Pixel for LinkedIn -->
<script type="text/javascript">
_linkedin_data_partner_id = "83195";
</script><script type="text/javascript">
(function(){var s = document.getElementsByTagName("script")[0];
var b = document.createElement("script");
b.type = "text/javascript";b.async = true;
b.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js";
s.parentNode.insertBefore(b, s);})();
</script>
<noscript>
<img height="1" width="1" style="display:none;" alt="" src="https://dc.ads.linkedin.com/collect/?pid=83195&fmt=gif" />
</noscript>
<!-- End Tracking Pixel for LinkedIn -->