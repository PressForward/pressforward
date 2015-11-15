		<p>
			<?php
				_e('The following options are advanced tools for admins attempting to test, debug, or execute advanced functionality. They are capable of breaking your retrieval process.', 'pf');
			?>
		</p>
		<div id="responses"></div>
		<p>
			<button type="submit" class="refreshfeed btn btn-small" id="refreshfeed" value="<?php  _e('Refresh', 'pf')  ?>"><?php  _e('Refresh', 'pf');  ?></button>
			<?php
				_e( ' the feed retrieval process. This button will attempt to restart a broken refresh process. If a previous feed retrieval cycle was completed, it will start the next one early. However, if the process is currently ongoing it will notify the system that you believe there is an error in the retrieval process, and the next time your site steps through the cycle, the system will attempt to find and rectify the error.', 'pf');
			?>
		</p>
		<p>
			<button type="submit" class="cleanfeeds btn btn-small" id="cleanfeeds" value="<?php  _e('Clean Up', 'pf')  ?>"><?php  _e('Clean Up', 'pf');  ?></button>
			<?php
				_e( ' the feed items. You can press this button to manually initiate the process of selecting feed items more than 2 months old. There is a chance of initiating this process simultaneously with a process triggered automatically every 30 minutes. If this occurs, error messages will likely appear in your server logs.', 'pf');
			?>
		</p>
		<?php
		do_action('pf_tools');
