<?php
return ['roles'=>[
 'sales_agent'=>['label'=>'Sales Agent','department'=>'sales','permissions'=>['box_office.sell','box_office.reprint']],
 'cashier'=>['label'=>'Cashier','department'=>'sales','permissions'=>['box_office.sell','box_office.reprint']],
 'ticket_checker'=>['label'=>'Ticket Checker','department'=>'admissions','permissions'=>['tickets.scan']],
 'box_office_supervisor'=>['label'=>'Box Office Supervisor','department'=>'sales','permissions'=>['box_office.sell','box_office.reprint','box_office.void_request','box_office.void_approve','shifts.verify','reports.view','tickets.scan']],
 'event_manager'=>['label'=>'Event Manager','department'=>'operations','permissions'=>['reports.view','tickets.scan']],
 'support_staff'=>['label'=>'Support Staff','department'=>'support','permissions'=>[]],
 ],'departments'=>['sales'=>'Sales / Box Office','admissions'=>'Admissions / Scanner','operations'=>'Event Operations','support'=>'Customer Support','other'=>'Other']];
