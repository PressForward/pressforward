		<p>
			<button type="submit" class="refreshfeed btn btn-small" id="refreshfeed" value="<?php  _e('Refresh', 'pf')  ?>"><?php  _e('Refresh', 'pf');  ?></button>
			<?php
				_e( ' the feed retrieval process. This button will attempt to restart a broken refresh process. If a previous feed retrieval cycle was completed, it will start the next one early. However, if the process is currently ongoing it will notify the system that you believe there is an error in the retrieval process, and the next time your site steps through the cycle, the system will attempt to find and rectify the error.', 'pf');
			?>
		</p>
		<?php
		do_action('pf_tools');
