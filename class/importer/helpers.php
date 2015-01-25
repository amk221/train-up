<?php

/**
 * General helper functions for working with the Importer
 *
 * @package Train-Up!
 * @subpackage Importer
 */

namespace TU;

class Importer {

  /**
   * $supported_types
   *
   * An array of file types that our Question importer supports.
   * Each one, when slugified translates to a function that handles the import,
   * e.g. text/xml -> import_textxml
   *
   * @var array
   *
   * @access public
   * @static
   */
  public static $supported_types = array(
    'text/xml'
  );

  /**
   * ajax_import
   *
   * - Callback for an AJAX request that attempts to import some Test Questions.
   *   Loop through the uploaded files, and import them.
   * - An import function is fired automatically depeding on the type of file
   *   being imported to handle the import.
   * - Each individual file import returns its successful import counts, which
   *   are merged into one lump sum.
   * 
   * @param integer $test_id ID of the Test to import the Questions to
   * @param array $files PHP uploaded files hash
   *
   * @access public
   * @static
   *
   * @return array Hash of info about the import attempt(s)
   */
  public static function ajax_import($test_id, $files) {
    if (!current_user_can('tu_backend')) return;

    $no_of_files = isset($files['name']) ? count($files['name']) : 0;
    $total       = 0;
    $imported    = 0;

    for ($i = 0; $i < $no_of_files; $i++) {
      if (!is_uploaded_file($files['tmp_name'][$i])) {
        continue;
      }

      $type     = $files['type'][$i];
      $filename = $files['tmp_name'][$i];
      $func     = __CLASS__.'::import_'.sanitize_key($type);
      $args     = array($test_id, $filename);

      if (in_array($type, self::$supported_types)) {
        $result   = call_user_func_array($func, $args);
        $total    += $result['total'];
        $imported += $result['imported'];
      }
    }

    $msg = tu()->message->view('error', __('Error importing questions', 'trainup'));

    if ($imported > 0) {
      $msg = tu()->message->view('success', sprintf(
        __('%1$s of %2$s questions imported', 'trainup'), $imported, $total)
      );
    }

    return compact('imported', 'total', 'msg');
  }

  /**
   * import_xml
   * 
   * @param integer $test_id ID of Test to import the Questions to
   * @param string $filename Filename that contains the XML to import
   *
   * @see http://docs.moodle.org/24/en/Moodle_XML_format
   * @access public
   * @static
   *
   * @return array Hash of info about the import attempt
   */
  public static function import_textxml($test_id, $filename) {
    $data       = simplexml_load_file($filename);
    $total      = 0;
    $imported   = 0;

    foreach ($data->question as $moo_question) {
      $moo_type = $moo_question->attributes()->type;

      if ($moo_type != 'category') {
        $total++;
      }
      if (($moo_type == 'multichoice' && $moo_question->single == 'true') || ($moo_type == 'truefalse')) {
        $type = 'multiple';
      } else if ($moo_type == 'shortanswer' || $moo_type == 'numerical') {
        $type = 'single';
      }
      if (!isset($type)) {
        continue;
      }

      $imported++;
      $question = Questions::factory(array(
        'post_title'   => sprintf(__('Question %1$s', 'trainup'), $imported),
        'post_type'    => "tu_question_{$test_id}",
        'menu_order'   => $imported,
        'post_content' => (string)$moo_question->questiontext->text
      ));
      $question->save();
      $question->set_test_id($test_id);
      $question->set_type($type);

      if ($type === 'multiple') {
        $answers = array();
        $correct = '';

        foreach ($moo_question->answer as $moo_answer) {
          $answer = (string)$moo_answer->text;

          if ($moo_type == 'truefalse') {
            $answer = ucfirst($answer);
          }
          if ($moo_answer->attributes()->fraction == 100) {
            $correct = $answer;
          }

          $answers[] = $answer;
        }

        $question->save_multiple_answers($answers, $correct);
      }
      else if ($type === 'single') {
        $question->save_single_answer(
          (string)$moo_question->answer->text, 'equal-to'
        );
      }
    }

    return compact('imported', 'total');
  }

  /**
   * localised_js
   *
   * Returns the hash of localised vars to be used with the importer JavaScript.
   * 
   * @access public
   * @static
   *
   * @return array
   */
  public static function localised_js() {
    return array(
      '_done'           => __('Done', 'trainup'),
      '_cancel'         => __('Cancel', 'trainup'),
      '_import'         => __('Import', 'trainup'),
      '_noFileSelected' => __('No file selected', 'trainup')
    );
  }

}


 
