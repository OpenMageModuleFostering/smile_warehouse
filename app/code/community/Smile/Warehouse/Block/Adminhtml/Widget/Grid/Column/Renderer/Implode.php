<?php
/**
 * Grid column widget for rendering grid cells that contains mapped values
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Block_Adminhtml_Widget_Grid_Column_Renderer_Implode extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Renders grid column
     *
     * @param Varien_Object $row row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $defaultValue = $this->getColumn()->getDefault();
        $data = parent::_getValue($row);
        if (is_array($data) && $data) {
            foreach ($data as &$str) {
                $str = htmlspecialchars($str);
            }
            $data = implode($this->getColumn()->getSeparator(), $data);
        } else {
            $data = htmlspecialchars((is_null($data) ? $defaultValue : $data));
        }

        return $data;
    }
}