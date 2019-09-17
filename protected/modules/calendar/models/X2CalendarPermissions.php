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
 * @package application.modules.calendar.models
 */
class X2CalendarPermissions extends CActiveRecord
{    
    /**
     * Returns the static model of the specified AR class.
     * @return Contacts the static model class
     */
    public static function model($className=__CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'x2_calendar_permissions';
    }
    
    public static function getViewableUserCalendarNames() {
        
        $calendarQuery = Yii::app()->db->createCommand()
                ->selectDistinct('a.id, a.name, (a.createdBy = :user) as mine')
                ->from('x2_calendars a')
                ->order('mine DESC');
        $params = array(
            ':user' => Yii::app()->user->name
        );
        if (!Yii::app()->params->isAdmin) {
            $calendarQuery->where('a.createdBy = :user OR (a.createdBy != :user AND b.userId = :userId AND b.view = 1)')
                    ->leftJoin('x2_calendar_permissions b', 'a.id = b.calendarId');
            $params[':userId'] = Yii::app()->user->id;
        }
        $calendars = $calendarQuery->queryAll(true, $params);
        $ret = array();
        foreach($calendars as $arr){
            $ret[$arr['id']] = $arr['name'];
        }
        return $ret;
    }
    
    public static function getEditableUserCalendarNames() {
        $calendarQuery = Yii::app()->db->createCommand()
                ->selectDistinct('a.id, a.name')
                ->from('x2_calendars a');
        if (!Yii::app()->params->isAdmin) {
            $calendarQuery->where('a.createdBy = :user OR (a.createdBy != :user AND b.userId = :userId AND b.edit = 1)',
                            array(
                        ':user' => Yii::app()->user->name,
                        ':userId' => Yii::app()->user->id
                    ))
                    ->leftJoin('x2_calendar_permissions b', 'a.id = b.calendarId');
        }
        $calendars = $calendarQuery->queryAll();
        $ret = array();
        foreach($calendars as $arr){
            $ret[$arr['id']] = $arr['name'];
        }
        return $ret;
    }
}