31/08/2019

ALTER TABLE  `ace_rp_sms_log` ADD  `phone_number` VARCHAR( 100 ) NOT NULL ,
ADD  `sms_type` TINYINT NOT NULL COMMENT  '1-send, 2-receive';

ALTER TABLE  `ace_rp_sms_log` CHANGE  `customer_id`  `customer_id` INT( 11 ) NULL DEFAULT  '0'
