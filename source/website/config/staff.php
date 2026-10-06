<?php
return ['roles'=>[
 'sales_agent'=>['label'=>'Sales Agent','department'=>'sales','permissions'=>['box_office.sell','box_office.reprint']],
 'cashier'=>['label'=>'Cashier','department'=>'sales','permissions'=>['box_office.sell','box_office.reprint']],
 'ticket_checker'=>['label'=>'Ticket Checker','department'=>'admissions','permissions'=>['tickets.scan','access.scan_entry','access.scan_exit']],
 'credential_issuer'=>['label'=>'Credential Issuer','department'=>'admissions','permissions'=>['credentials.issue','credentials.replace']],
 'box_office_supervisor'=>['label'=>'Box Office Supervisor','department'=>'sales','permissions'=>['box_office.sell','box_office.reprint','box_office.void_request','box_office.void_approve','shifts.verify','reports.view','tickets.scan','credentials.issue','credentials.replace','credentials.inventory','credentials.revoke','access.scan_entry','access.scan_exit','access.override','access.reports']],
 'event_manager'=>['label'=>'Event Manager','department'=>'operations','permissions'=>['reports.view','tickets.scan','credentials.issue','credentials.replace','credentials.inventory','credentials.revoke','access.scan_entry','access.scan_exit','access.override','access.reports']],
 'support_staff'=>['label'=>'Support Staff','department'=>'support','permissions'=>[]],
 ],'departments'=>['sales'=>'Sales / Box Office','admissions'=>'Admissions / Scanner','operations'=>'Event Operations','support'=>'Customer Support','other'=>'Other']];
