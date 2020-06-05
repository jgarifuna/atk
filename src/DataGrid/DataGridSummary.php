<?php

namespace Sintattica\Atk\DataGrid;

use Sintattica\Atk\Utils\StringParser;

/**
 * The data grid summary. Can be used to render a
 * summary for an ATK data grid.
 *
 * @author Peter C. Verhage <peter@achievo.org>
 */
class DataGridSummary extends DataGridComponent
{
    /**
     * Renders the summary for the given data grid.
     *
     * @return string rendered HTML
     */
    public function render()
    {
        $grid = $this->getGrid();

        $limit = $grid->getLimit();
        $count = $grid->getCount();

        if ($count == 0) {
            return;
        }

        if ($limit == -1) {
            $limit = $count;
        }

        // Added by Jorge Garifuna:
        //  We shouldn't expect limit to be 0, but for some reason it happens and this avoids dividing by 0 later on
        if ($limit == 0) {
            return;
        }            
        
        $start = $grid->getOffset();
        $end = min($start + $limit, $count);
        $page = floor(($start / $limit) + 1);
        $pages = ceil($count / $limit);

        $string = $grid->text('datagrid_summary');

        $params = array(
            'start' => $start + 1,
            'end' => $end,
            'count' => $count,
            'limit' => $limit,
            'page' => $page,
            'pages' => $pages,
        );

        $parser = new StringParser($string);
        $result = $parser->parse($params);

        return '<div class="dgridsummary">'.$result.'</div>';
    }
}
