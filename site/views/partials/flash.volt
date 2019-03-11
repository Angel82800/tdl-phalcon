<?php
$messages = $this->flashSession->getMessages();

if (! empty($messages)) {
    foreach ($messages as $type => $message) {
    	if (! empty($message) && $message[0]) { ?>
	    <div class="<?php echo $type; ?> callout flex-center-vertically" data-closable>
	        <p><?php echo implode('<br />', $message); ?></p>
	        <button class="close-button" aria-label="Dismiss alert" type="button" data-close>
	            <span aria-hidden="true">&times;</span>
	        </button>
	    </div>
<?php
		}
    }
}
