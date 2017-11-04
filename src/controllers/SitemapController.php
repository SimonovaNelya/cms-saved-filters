<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.11.2016
 */
namespace skeeks\cms\savedFilters\controllers;

use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsTree;
use skeeks\cms\modules\admin\widgets\Tree;
use skeeks\cms\savedFilters\models\SavedFilters;
use yii\helpers\Url;
use yii\web\Response;

class SitemapController extends \skeeks\cms\seo\controllers\SitemapController
{
/**
     * @return string
     */
    public function actionOnRequest()
    {
        ini_set("memory_limit","512M");

        $trees = CmsTree::find()->where(['site_code' => \Yii::$app->cms->site->code])->orderBy(['level' => SORT_ASC, 'priority' => SORT_ASC])->all();

        if ($trees)
        {
            /**
             * @var Tree $tree
             */
            foreach ($trees as $tree)
            {
                if (!$tree->redirect)
                {
                    $result[] =
                    [
                        "loc"           => $tree->url,
                        "lastmod"       => $this->_lastMod($tree),
                    ];
                }
            }
        }

        $elements = CmsContentElement::find()
                    ->joinWith('cmsTree')
                    ->andWhere([CmsTree::tableName() . '.site_code' => \Yii::$app->cms->site->code])
                    ->orderBy(['updated_at' => SORT_DESC, 'priority' => SORT_ASC])
                    ->all();
        //Добавление элементов в карту
        if ($elements)
        {
            /**
             * @var CmsContentElement $model
             */
            foreach ($elements as $model)
            {
                $result[] =
                [
                    "loc"           => $model->absoluteUrl,
                    "lastmod"       => $this->_lastMod($model),
                ];
            }
        }

        $filters = SavedFilters::find()->where(['is_active' => 1])->all();

        if ($filters)
        {
            foreach ($filters as $filter)
            {
                $result[] =
                [
                    "loc"           => $filter->getUrl(true),
                    "lastmod"       => $this->_lastMod($filter),
                ];
            }
        }

        $result[] = [
            'loc' => Url::to(['/skeeks-cms'], true)
        ];

        \Yii::$app->response->format = Response::FORMAT_XML;
        $this->layout                = false;

        //Генерация sitemap вручную, не используем XmlResponseFormatter
        \Yii::$app->response->content =  $this->render($this->action->id, [
            'data' => $result
        ]);

        return;
    }


    /**
     * @param Tree $model
     * @return string
     */
    private function _lastMod($model)
    {
        $string = "2013-08-03T21:14:41+01:00";
        $string = date("Y-m-d", $model->updated_at) . "T" . date("H:i:s+04:00", $model->updated_at);

        return $string;
    }

    /**
     * @param Tree $model
     * @return string
     */
    private function _calculatePriority($model)
    {
        $priority = '0.4';
        if ($model->level == 0)
        {
            $priority = '1.0';
        } else if($model->level == 1)
        {
            $priority = '0.8';
        } else if($model->level == 2)
        {
            $priority = '0.7';
        } else if($model->level == 3)
        {
            $priority = '0.6';
        } else if($model->level == 4)
        {
            $priority = '0.5';
        }

        return $priority;
    }
}