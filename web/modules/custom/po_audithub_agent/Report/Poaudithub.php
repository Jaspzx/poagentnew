<?php
/**
 * @file
 * Classname for the Poaudithub 'report', i.e. the section in the Site Audit
 * report for this module's audits.
 *
 * Classname must not have namespaces and must start with
 * SiteAuditReport[report_name] - see Site Audit README on
 * http://cgit.drupalcode.org/site_audit/tree/README.md
 */


class SiteAuditReportPoaudithub extends \SiteAuditReportAbstract {

  /**
   * Implements \SiteAudit\Report\Abstract\getLabel().
   */
  public function getLabel() {
    return dt('POAuditHub');
  }
}
