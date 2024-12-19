<?php
include_once 'init.php';
function renderActivities($filter = [])
{
    global $Groups;
    $Format = new Document(true);
    $DB = new DB;
    $cursor = $DB->db->activities->find($filter);
    $rendered = [
        'print' => '',
        'web' => '',
        'depts' => '',
        'icon' => '',
        'type' => '',
    ];
    foreach ($cursor as $doc) {
        $id = $doc['_id'];
        $Format->setDocument($doc);
        $Format->usecase = 'web';
        $doc['authors'] = DB::doc2Arr($doc['authors']);

        $depts = $Groups->getDeptFromAuthors($doc['authors']);

        $f = $Format->format();
        $web = $Format->formatShort();

        $Format->usecase = 'portal';
        $portfolio = $Format->formatPortfolio();

        $rendered = [
            'print' => $f,
            'plain' => strip_tags($f),
            'portfolio' => $portfolio,
            'web' => $web,
            'depts' => $depts,
            'icon' => trim($Format->activity_icon()),
            'type' => $Format->activity_type(),
            'subtype' => $Format->activity_subtype(),
            'title' => $Format->getTitle(),
            'authors' => $Format->getAuthors('authors'),
        ];
        $values = ['rendered' => $rendered];

        $values['start_date'] = valueFromDateArray($doc['start'] ?? $doc);
        if (array_key_exists('end', DB::doc2Arr($doc)) && is_null($doc['end'])) {
            $end = null;
        } else {
            $end = valueFromDateArray($doc['end'] ?? $doc['start'] ?? $doc);
        }
        $values['end_date'] = $end;

        if ($doc['type'] == 'publication' && isset($doc['journal'])) {
            // update impact if necessary
            $if = $DB->get_impact($doc);
            if (!empty($if)) {
                $values['impact'] = $if;
            }
            $values['quartile'] = $DB->get_metrics($doc);
        }
        $aoi_authors = array_filter($doc['authors'], function ($a) {
            return $a['aoi'] ?? false;
        });
        $values['affiliated'] = !empty($aoi_authors);
        $values['affiliated_positions'] = $Format->getAffiliationTypes();
        $values['cooperative'] = $Format->getCooperationType($values['affiliated_positions'], $depts);
        
        $DB->db->activities->updateOne(
            ['_id' => $id],
            ['$set' => $values]
        );
    }
    // return last element in case that only one id has been rendered
    return $rendered;
}
