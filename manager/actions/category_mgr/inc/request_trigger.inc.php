<?php
/**
 * Ajax Requests
 */
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

if( isset( $_REQUEST[$cm->get('request_key')]['ajax'] ) )
{
    $_data  = $_REQUEST[$cm->get('request_key')];
    $output = '';
    $task   = $_data['task'];
    switch( $task )
    {
        /**
         * get categories
         */
        case 'categorize_load_elements':
            $elements = $_data['elements'];

            if( $uncategorized_elements = $cm->getAssignedElements( 0, $_data['elements'] ) )
            {
               $output .= $cm->renderView('chunks/categorize/uncategorized_elements', $uncategorized_elements);
            }

            foreach( $cm->getCategories() as $category )
            {
                $category['elements'] = $cm->getAssignedElements( $category['id'], $_data['elements'] );
                $output .= $cm->renderView('chunks/categorize/category', $category);
            }
            break;
    }
    exit( $output );
}
/**
 * Categorize elements
 *
 * @notice array [data] removed
 * @see /manager/includes/protect.inc.php ($limit)
 * @see http://modxcms.com/forums/index.php/topic,40430.msg251476.html#msg251476
 *
 */
if( isset( $_POST[$cm->get('request_key')]['categorize']['submit'] ) )
{
    $_data = $_POST[$cm->get('request_key')]['categorize'];
    $_changes = 0;

    $cm->addMessage(
        sprintf(
            $cm->txt('cm_categorize_x'),
            $cm->txt($_data['elementsgroup'])
        ),
        'categorize'
    );

    if( !isset( $_data['elements'] ) )
    {
        $cm->addMessage( $cm->txt('cm_unknown_error'), 'categorize' );
        return;
    }

    foreach( $_data['elements'] as $element_id => $data )
    {
        if( $cm->updateElement( $_data['elementsgroup'], $element_id, $data['category_id'] ) )
        {
            $cm->addMessage(
                sprintf(
                    $cm->txt('cm_x_assigned_to_category_y'),
                    $data['element_name'],
                    $element_id,
                    $data['category_name'],
                    $data['category_id']
                ),
                'categorize'
            );
            $_changes++;
        }
    }

    if( $_changes === 0 )
    {
        $cm->addMessage( $cm->txt('cm_no_categorization'), 'categorize' );
        return;
    }
    else
    {
        $cm->addMessage(
            sprintf(
                $cm->txt('cm_x_changes_made'),
                $_changes
            ),
            'categorize'
        );
    }
}

/**
 * Add a new category
 */
if( isset( $_POST[$cm->get('request_key')]['add']['submit'] ) )
{
    $_data    = $_POST[$cm->get('request_key')]['add']['data'];
    $category = trim( html_entity_decode($_data['name']) );
    $rank     = (int) $_data['rank'];

    if( empty( $category ) )
    {
       $cm->addMessage( $cm->txt('cm_enter_name_for_category'), 'add' );
       return;
    }

    if( $cm->isCategoryExists( $category ) )
    {
       $cm->addMessage( sprintf( $cm->txt('cm_category_x_exists'), $category ), 'add' );
       return;
    }

    if( $cm->addCategory( $category, $rank ) !== 0 )
    {
        $cm->addMessage(
            sprintf(
                $cm->txt( 'cm_category_x_saved_at_position_y' ),
                $category,
                $rank
            ),
            'add'
        );
    }
    else
    {
        $cm->addMessage( $cm->txt('cm_unknown_error'), 'add' );
    }
}

/**
 * Sort categories
 */
if( isset( $_POST[$cm->get('request_key')]['sort']['submit'] ) )
{
    $categories = $_POST[$cm->get('request_key')]['sort']['data'];
    $_changes   = 0;

    foreach( $categories as $category_id => $_data  )
    {
        $data = [
            'category' => urldecode( $_data['category'] ),
            'rank'     => $_data['rank']
        ];

        if( $cm->updateCategory( $category_id, $data ) )
        {
            $cm->addMessage(
                sprintf(
                    $cm->txt('cm_category_x_moved_to_position_y'),
                    $data['category'],
                    $data['rank']
                ),
                'sort'
            );
            $_changes++;
        }
    }

    if( $_changes === 0 )
    {
        $cm->addMessage( $cm->txt( 'cm_no_changes' ), 'sort');
    }
    else
    {
        $cm->addMessage(
            sprintf(
                $cm->txt('cm_x_changes_made'),
                $_changes
            ),
            'sort'
        );
    }
}

/**
 * Edit categories
 */
if( isset( $_POST[$cm->get('request_key')]['edit']['submit'] ) )
{
    $categories = $_POST[$cm->get('request_key')]['edit']['data'];
    $_changes   = 0;

    foreach( $categories as $category_id => $_data  )
    {
        if( isset( $_data['delete'] ) )
        {
            if( $cm->deleteCategory( $category_id ) )
            {
                $cm->addMessage(
                    sprintf(
                        $cm->txt('cm_category_x_deleted'),
                        urldecode( $_data['origin'] )
                    ),
                    'edit'
                );
            }
            $_changes++;
            continue;
        }

        $data = [
            'category' => trim( html_entity_decode( $_data['category'] ) ),
            'rank'     => $_data['rank']
        ];

        if( $cm->updateCategory( $category_id, $data ) )
        {
            $cm->addMessage(
                sprintf(
                    $cm->txt('cm_category_x_renamed_to_y'),
                    urldecode( $_data['origin'] ),
                    $data['category']
                ),
                'edit'
            );
            $_changes++;
        }
    }

    if( $_changes === 0 )
    {
        $cm->addMessage( $cm->txt( 'cm_no_changes' ), 'edit');
    }
}

/**
 * Delete singel category by $_GET
 */
if( isset( $_GET[$cm->get('request_key')]['delete'] )
    && !empty( $_GET[$cm->get('request_key')]['delete'] ) )
{
    $category_id = (int)$_GET[$cm->get('request_key')]['delete'];

    if( $cm->deleteCategory( $category_id ) )
    {
        $cm->addMessage(
            sprintf(
                $cm->txt('cm_category_x_deleted'),
                urldecode( $_GET[$cm->get('request_key')]['category'] )
            ),
            'edit'
        );
    }
}
/**
 * Translate phrases
 */
if( isset( $_POST[$cm->get('request_key')]['translate']['submit'] ) )
{
    $translations = $_POST[$cm->get('request_key')]['translate']['data'];

    foreach( $translations as $native_phrase => $translation )
    {
        $native_phrase = urldecode( $native_phrase );

        if( empty( $translation ) )
        {
            $translation = $native_phrase;

            $cm->addMessage(
                sprintf(
                    $cm->txt('cm_translation_for_x_empty'),
                    $native_phrase
                ),
                'translate'
            );
        }

        $cm->c('Translator')->addTranslation( $native_phrase, $translation, 'phrase' );

        $cm->addMessage(
            sprintf(
                $cm->txt('cm_translation_for_x_to_y_success'),
                $native_phrase,
                $translation
            ),
            'translate'
        );
    }
}
