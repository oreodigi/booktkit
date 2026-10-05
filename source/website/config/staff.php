<?php
return [
 'roles'=>[
  'sales_agent'=>['label'=>'Sales Agent','permissions'=>['box_office.sell','box_office.reprint']],
  'ticket_checker'=>['label'=>'Ticket Checker','permissions'=>['tickets.scan']],
  'box_office_supervisor'=>['label'=>'Box Office Supervisor','permissions'=>['box_office.sell','box_office.reprint','box_office.void_request','box_office.void_approve','shifts.verify','reports.view','tickets.scan']],
 ],
];