<?php
namespace PressForward\Core\AJAX;

use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Controllers\Metas;
use PressForward\Controllers\PF_to_WP_Posts;
use PressForward\Core\Schema\Feed_Items;
use WP_Ajax_Response;

class ItemsAJAX implements HasActions
{

    protected $basename;

    public function __construct(Metas $metas, PF_to_WP_Posts $posts, Feed_Items $items)
    {
        $this->metas = $metas;
        $this->posts = $posts;
        $this->items = $items;

    }

    public function action_hooks()
    {
        return array(
            array(
                'hook' => 'wp_ajax_pf_ajax_get_meta_fields',
                'method' => 'pf_ajax_get_meta_fields',
            ),
        );
    }

    private function validate_meta_for_edit($a_meta, $post_level = 'nomination')
    {
        $meta_type = $a_meta['type'];
        if (in_array('dep', $meta_type) || !in_array($post_level, $a_meta['level'])) {
            return false;
        } else if (in_array('adm', $meta_type) || in_array('desc', $meta_type)) {
            return true;
        } else {
            return false;
        }

    }

    public function pf_ajax_get_meta_fields()
    {
        $innerbox = '<div>';
        foreach ($this->metas->structure() as $a_meta) {
            if ($this->validate_meta_for_edit($a_meta)) {
                $innerbox .= '';
            }
        }
		$innerbox .= '</div>';
        ob_start();
        if (isset($_POST['post_id'])) {
            $id = $_POST['post_id'];
        } else {
            pressforward('ajax.configuration')->pf_bad_call('pf_ajax_thing_deleter', 'No item.');
        }

        $vd = ob_get_clean();

        $response = array(
            'what' => 'pressforward',
            'action' => 'pf_ajax_thing_deleter',
            'id' => $id,
            'data' => (string) $vd,
        );
        $xmlResponse = new WP_Ajax_Response($response);
        $xmlResponse->send();
        ob_end_clean();
        die();

    }

}
