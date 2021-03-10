<?php
/**
 * "Load help text" view helper
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace VuFind\View\Helper\Root;

/**
 * "Load help text" view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class HelpText extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * The context view helper
     *
     * @var Content
     */
    protected $contentHelper;

    /**
     * Warning messages
     *
     * @var array
     */
    protected $warnings = [];

    /**
     * Constructor
     *
     * @param Content $content The context view helper
     */
    public function __construct(Content $content)
    {
        $this->contentHelper = $content;
    }

    /**
     * Get warnings generated during rendering (if any).
     *
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * Render a help template (or return false if none found).
     *
     * @param string $name    Template name to render
     * @param array  $context Variables needed for rendering template; these will
     * be temporarily added to the global view context, then reverted after the
     * template is rendered (default = empty).
     *
     * @return string|bool
     */
    public function render($name, $context=[])
    {
        // Sanitize the template name to include only alphanumeric characters
        // or underscores.
        $safe_topic = preg_replace('/[^\w]/', '', $name);

        $this->warnings = [];
        $html = $this->contentHelper->renderTranslated(
            $safe_topic,
            'HelpTranslations',
            $context,
            $pageDetails,
        );

        if (!$html) {
            $this->warnings[] = 'Sorry, but the help you requested is '
                . 'not available.';
        } elseif ($pageDetails['type'] != 'language') {
            $this->warnings[] = 'Sorry, but the help you requested is '
                . 'unavailable in your language.';
        }

        return $html;
    }
}
