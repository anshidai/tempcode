<?php 

class attribute
{

        public function getAttr( $goods_type, &$attr_input_type, &$attr_values, &$attr_type )
        {
                global $db;
                global $ecs;
                $sql = "SELECT * FROM ".$ecs->table( "attribute" )." a ,".$ecs->table( "goods_type" )." gt WHERE a.cat_id=gt.cat_id AND gt.cat_id='".$goods_type."'";
                $res = $db->query( $sql );
                while ( $row = $db->fetchRow( $res ) )
                {
                        $attr_input_type[$row['attr_id']] = $row['attr_input_type'];
                        $attr_type[$row['attr_id']] = $row['attr_type'];
                        $attr_values[$row['attr_id']] = $row['attr_values'];
                }
        }

        public function attrFormat( $attr, $attr_input_type, $attr_values, $attr_type )
        {
                global $db;
                global $ecs;
                global $_POST;
                foreach ( $attr as $attr_id => $attr_value )
                {
                        $is_modify = 0;
                        $attr_value = trim($attr_value);
                        if (!empty($attr_value))
                        {
                                if ( $attr_input_type[$attr_id] == 1 && $attr_type[$attr_id] == 0 )
                                {
                                        $attr_values[$attr_id] = explode( "\r\n", $attr_values[$attr_id] );
                                        if ( !in_array( $attr_value, $attr_values[$attr_id] ) )
                                        {
                                                array_push( $attr_values[$attr_id], $attr_value );
                                                $is_modify = 1;
                                        }
                                        $attr_values[$attr_id] = implode( "\r\n", $attr_values[$attr_id] );
                                }
                                else if ( ( $attr_input_type[$attr_id] == 1  && $attr_type[$attr_id] == 1) || ($attr_input_type[$attr_id] == 1 && $attr_type[$attr_id] == 2) )
                                {
                                        $attr_values[$attr_id] = explode( "\r\n", $attr_values[$attr_id] );
                                        $attr[$attr_id] = explode( "|||", $attr_value );
                                        foreach ( $attr[$attr_id] as $key => $attr_value )
                                        {
                                                if ( !$attr_value )
                                                {
                                                }
                                                else if ( !in_array( $attr_value, $attr_values[$attr_id] ) )
                                                {
                                                        array_push( $attr_values[$attr_id], $attr_value );
                                                        $is_modify = 1;
                                                }
                                        }
                                        $attr_values[$attr_id] = implode( "\r\n", $attr_values[$attr_id] );
                                }
                                if ( $is_modify )
                                {
                                        $sql = "UPDATE ".$ecs->table( "attribute" )." SET attr_values = '".$attr_values[$attr_id]. "' WHERE attr_id = '".$attr_id."'";
                                        $db->query( $sql );
                                }
                        }
                }
                reset( $attr );
                foreach ( $attr as $attr_id => $attr_value )
                {
                        if ( is_array( $attr_value ) )
                        {
                                foreach ( $attr_value as $key => $value )
                                {
                                        array_push( $GLOBALS['_POST']['attr_id_list'], $attr_id );
                                        array_push( $GLOBALS['_POST']['attr_price_list'], get_sub_info( $value, "price" ) );
                                        array_push( $GLOBALS['_POST']['attr_value_list'], $value );
                                }
                        }
                        else
                        {
                                array_push( $GLOBALS['_POST']['attr_id_list'], $attr_id );
                                array_push( $GLOBALS['_POST']['attr_price_list'], get_sub_info( $attr_value, "price" ) );
                                array_push( $GLOBALS['_POST']['attr_value_list'], $attr_value );
                        }
                }
        }

        public function isExists( $attr_name, $goods_type )
        {
                global $db;
                global $ecs;
                $sql = "SELECT attr_id FROM ".$ecs->table( "attribute" ). " WHERE attr_name = '".$attr_name."' AND cat_id = '{$goods_type}'";
                $res = $db->query( $sql );
                $row = $db->fetchRow( $res );
                if ( $row )
                {
                        return $row['attr_id'];
                }
                return 0;
        }

        public function insert( $attr_name, $goods_type, $attr_group = 0, $attr_index = 1, $is_linked = 0, $attr_type = 0, $attr_input_type = 0, $attr_values = "", $attr_id = 0 )
        {
                global $db;
                global $ecs;
                $attr_id = $this->isExists( $attr_name, $goods_type );
                if ( $attr_id )
                {
                        return $attr_id;
                }
                $attr = array(
                        "cat_id" => $goods_type,
                        "attr_name" => $attr_name,
                        "attr_index" => $attr_index,
                        "attr_input_type" => $attr_input_type,
                        "is_linked" => $is_linked,
                        "attr_values" => isset( $attr_values ) ? $attr_values : "",
                        "attr_type" => empty( $attr_type ) ? "0" : intval( $attr_type ),
                        "attr_group" => isset( $attr_group ) ? intval( $attr_group ) : 0
                );
                $db->autoExecute( $ecs->table( "attribute" ), $attr, "INSERT" );
                return $db->insert_id( );
        }

}