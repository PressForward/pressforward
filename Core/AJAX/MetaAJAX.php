<?php

namespace PressForward\Core\AJAX;

use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Controllers\Metas;
use PressForward\Controllers\PF_to_WP_Posts;
use PressForward\Core\Schema\Feed_Items;
use WP_Ajax_Response;

class MetaAJAX implements HasActions {

	protected $basename;

	public function __construct( Metas $metas, PF_to_WP_Posts $posts, Feed_Items $items ) {
		$this->metas = $metas;
		$this->posts = $posts;
		$this->items = $items;
	}

	public function action_hooks() {
		return array(
			array(
				'hook'   => 'wp_ajax_pf_ajax_update_meta_fields',
				'method' => 'pf_ajax_update_meta_fields',
			),
			array(
				'hook'     => 'pf_output_additional_modals',
				'method'   => 'meta_labels_modal_box',
				'priority' => 10,
				'args'     => 3,
			),
		);
	}

	private function validate_meta_for_edit( $a_meta, $post_level = 'nomination', $post_id = false ) {
		$meta_type = $a_meta['type'];
		if ( in_array( 'aggr', $meta_type ) && false !== $post_id ) {
			if ( 0 != wp_get_post_parent_id( $post_id ) ) {
				return false;
			}
		}
		if ( in_array( 'dep', $meta_type ) || ! in_array( $post_level, $a_meta['level'] ) ) {
			return false;
		} elseif ( in_array( 'adm', $meta_type ) || in_array( 'desc', $meta_type ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function create_meta_field( $id, $a_meta ) {
		$meta_value = $this->metas->get_post_pf_meta( $id, $a_meta['name'] );
		if ( is_array( $meta_value ) ) {
			return '';
		}
		$field = <<<EOT
			<li>
				<label for="{$id}-meta-form[{$a_meta['name']}]">
				{$a_meta['title']}:
					<input type="text" id="{$id}-meta-form[{$a_meta['name']}]" name="{$a_meta['name']}" value="{$meta_value}">
					</input><br />
					<span class="meta-details"><sup>{$a_meta['definition']}</sup></span><br />
				</label>

			</li>
EOT;

		return $field;
	}

	public function meta_labels_modal_box( $item, $c = 0, $format ) {
		// var_dump($item, $c, $format); die();
		// echo '<!-- meta_labels_modal_box: '+$item+$c+$format+' -->';
		if ( $format !== 'nomination' ) {
			echo '<!-- meta_labels_modal_box: not nomination -->';

			return $item;
		}
		if ( ! current_user_can( get_option( 'pf_menu_nominate_this_access', pressforward( 'controller.users' )->pf_get_defining_capability_by_role( 'administrator' ) ) ) ) {
			echo '<!-- meta_labels_modal_box: not high enough level permissions -->';

			return $item;
		}
		$innerbox = '<div class="meta-inputs"><ul>';
		foreach ( $this->metas->structure() as $a_meta ) {
			if ( $this->validate_meta_for_edit( $a_meta, 'nomination', $item['post_id'] ) ) {
				$innerbox .= $this->create_meta_field( $item['post_id'], $a_meta );
			}
		}
		$innerbox .= '</ul></div>'; ?>
			<div id="meta_form_modal_<?php echo $item['post_id']; ?>" class="modal fade meta-form-modal" tabindex="-1" role="dialog" aria-labelledby="meta_form_modal_<?php echo $item['post_id']; ?>_label" aria-hidden="true">
			  <div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
				<h3 id="meta_form_modal_<?php echo $item['post_id']; ?>_label">Metadata</h3>
			  </div>
			  <div class="modal-body">
				<?php
					echo $innerbox;
				$nonce = wp_create_nonce( 'meta_form_nonce_' . $item['post_id'] );
				echo '<input type="hidden" id="meta_form_' . $item['post_id'] . '_nonce_wpnonce" name="_wpnonce" value="' . $nonce . '">';
		?>
			  </div>
			  <div class="modal-footer">
				<button type="button" class="save btn btn-success meta_form-save" data-post-id="<?php echo $item['post_id']; ?>" aria-hidden="false" onclick="pf.metaEdit(this)" >Save</button>
				<button class="btn close-button" data-dismiss="modal" aria-hidden="true">Close</button>
			  </div>
			</div>
		<?php
	}

	public function pf_ajax_update_meta_fields() {
		if ( isset( $_POST['post_id'] ) ) {
			$id = $_POST['post_id'];
		} else {
			pressforward( 'ajax.configuration' )->pf_bad_call( 'pf_ajax_update_meta_fields', 'No post id.' );
		}

		if ( false == wp_verify_nonce( $_POST['nonce'], 'meta_form_nonce_' . $id ) ) {
			pressforward( 'ajax.configuration' )->pf_bad_call( 'pf_ajax_update_meta_fields', 'Failed Nonce.' );
		}

		ob_start();
		foreach ( $_POST['metadata'] as $key => $value ) {
			switch ( $key ) {
				case 'item_date':
					if ( ! empty( $value ) ) {
						$this->metas->update_pf_meta( $id, $key, $value );
						$d = \DateTime::createFromFormat( 'Y-m-d H:i:s', trim( $value ) );
						if ( ! $d ) {
							$d = strtotime( trim( $value ) );
							$d = DateTime::createFromFormat( 'U', $d );
						}
						if ( ! $d ) {
							pf_log( __( 'Cannot find date', 'pf' ) );
						} else {
							$this->metas->update_pf_meta( $id, 'sortable_item_date', $d->getTimestamp() );
						}
					}
					break;
				default:
					$this->metas->update_pf_meta( $id, $key, $value );
					break;
			}
		}

		$response    = array(
			'what'   => 'pressforward',
			'action' => 'pf_ajax_update_meta_fields',
			'id'     => $id,
			'data'   => (string) ob_get_flush(),
		);
		$xmlResponse = new WP_Ajax_Response( $response );
		// ob_end_clean();
		$xmlResponse->send();
		die();
	}
}
