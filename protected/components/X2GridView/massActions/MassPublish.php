<?php
/***********************************************************************************
 * X2CRM is a customer relationship management program developed by
 * X2Engine, Inc. Copyright (C) 2011-2016 X2Engine Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY X2ENGINE, X2ENGINE DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact X2Engine, Inc. P.O. Box 66752, Scotts Valley,
 * California 95067, USA. on our website at www.x2crm.com, or at our
 * email address: contact@x2engine.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * X2Engine" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by X2Engine".
 **********************************************************************************/



/**
 * Base class for all mass actions that publish actions to selected records 
 */

abstract class MassPublish extends MassAction {

    public $type;

    public function getPackages () {
        return array_merge (parent::getPackages (), array (
            'MassPublish' => array(
                'baseUrl' => Yii::app()->request->baseUrl,
                'js' => array(
                    'js/X2GridView/MassPublish.js',
                ),
                'depends' => array ('X2MassAction'),
            ),
            'MassPublishCss' => array(
                'baseUrl' => Yii::app()->theme->baseUrl,
                'css' => array(
                    'css/components/X2GridView/massActions/MassPublish.css',
                ),
            ),
        ));
    }

    abstract protected function getMessages ();

    public function execute (array $gvSelection) {
        if (!Yii::app()->user->checkAccess ('ActionsBasicAccess')) 
            Yii::app ()->controller->denied ();

        $modelName = $this->getModelName ();
        if (!isset ($_POST[$modelName])) {
            throw new CHttpException (400, Yii::t('app', 'Bad request.'));
            return;
        }

        $model = $this->getModel ();
        $model->setAttributes ($_POST[$modelName]);
        $model->validate ();
        $model->clearErrors ('associationId');
        $model->clearErrors ('associationName');
        if ($model->hasErrors ()) {
            self::$responseForm = self::getActionForm ($model);
            return;
        }

        // special value to indicate that this is a validation-only request
        if ($gvSelection === array (null)) { 
            return;
        }

        $unauthorized = 0;
        $saved = 0;
        $modelType = Yii::app ()->controller->modelClass;
        $messages = $this->getMessages ();
        foreach ($gvSelection as $recordId) {
            $association = $modelType::model ()->findByPk ($recordId);
            if ($association === null || 
                !Yii::app()->controller->checkPermissions ($association, 'view')) {
            
                $unauthorized++;
            } else {
                $model->clearErrors ();
                $model->associationId = $association->id;

                $action = $model->getAction (true);
                if ($model->validate ()) {
                    if ($action->save ()) {
                        $saved++;
                        $model->associationName = null;
                        $model->getAction (true); // refresh internal action model
                        continue;
                    }
                }
                self::$errorFlashes[] = $messages['failed'] ($association);
            }
        }

        if($saved > 0){
            self::$successFlashes[] = $messages['saved'] ($saved);
        } 
        if($unauthorized > 0){
            self::$errorFlashes[] = $messages['unauthorized'] ($unauthorized);
        } 
    }

    protected function getModelName () {
        return ucfirst ($this->type ? $this->type : 'Action').'FormModel';
    }

    protected function getModel () {
        $modelType = $this->getModelName ();
        $model = new $modelType;
        $model->assignedTo = Yii::app ()->user->getName ();
        $model->associationType = X2Model::getAssociationType (Yii::app()->controller->modelClass);
        return $model;
    }

    protected function getViewFile () {
        return 'application.modules.actions.views.actions._'.
            ($this->type ? $this->type : 'action').'Form';
    }

    protected function getActionForm ($model = null) {
        if (!$model) $model = $this->getModel ();
        static $i = 0;
        return Yii::app ()->controller->renderPartial ($this->getViewFile (), array (
            'model' => $model,
            'namespace' => get_called_class ().'ActionForm'.$i++,
            'htmlOptions' => array (
                'class' => 'mass-publish-form form2'
            )
        ), true);
    }

}

?>
