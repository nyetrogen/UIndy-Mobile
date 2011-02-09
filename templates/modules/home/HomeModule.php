<?php
/**
  * @package Module
  * @subpackage Home
  */

/**
  * @package Module
  * @subpackage Home
  */
class HomeModule extends Module {
  protected $id = 'home';

  protected function getModuleDefaultData() {
    return array_merge(parent::getModuleDefaultData(), array(
      'display_type'      => 'springboard',
      'primary_modules'   => array(),
      'secondary_modules' => array()
    ));
  }
  
  protected function getSectionTitleForKey($key) {
    switch ($key) {
        case 'primary_modules': return 'Primary Modules';
        case 'secondary_modules': return 'Secondary Modules';
        default: return parent::getSectionTitleForKey($key);
    }
  }
  
  protected function prepareAdminForSection($section, &$adminModule) {
    switch ($section) {
        case 'primary_modules':
        case 'secondary_modules':
            $adminModule->setTemplatePage('module_order', $this->id);
            $adminModule->addInternalJavascript("/modules/{$this->id}/javascript/admin.js");
            $adminModule->addInternalCSS("/modules/{$this->id}/css/admin.css");

            $allModules = $this->getAllModules();
            $navigationModules = $this->getNavigationModules();

            foreach ($allModules as $moduleID=>$module) {
                $allModules[$moduleID] = $module->getModuleName();
            }
            
            $adminModule->assign('allModules', $allModules);
            $adminModule->assign('sectionModules', $navigationModules[$section]);
            break;
        default:
            return parent::prepareAdminForSection($section, $adminModule);
    }
  }
  
  private function getTabletModulePanes($tabletConfig) {
    $modulePanes = array();
    
    foreach ($tabletConfig as $blockName => $moduleID) {
      $path = self::getPathSegmentForModuleID($moduleID);
    
      $module = self::factory($path, 'pane', $this->args);
      
      $paneContent = $module->fetchPage(); // sets pageTitle var
      
      $modulePanes[$blockName] = array(
        'id' => $moduleID,
        'url' => $this->buildURLForModule($moduleID, 'index'),
        'title' => $module->getTemplateVars('pageTitle'),
        'content' => $paneContent,
      );
    }
    
    return $modulePanes;
  }
     
  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
        
      case 'pane':
        break;
        
      case 'index':
        $this->addOnLoad('rotateScreen();');
        $this->addOnOrientationChange('rotateScreen();');

        if ($this->pagetype == 'tablet') {
          $this->assign('modulePanes', $this->getTabletModulePanes($homeConfig['tabletPanes']));
          $this->addOnLoad('moduleHandleWindowResize();');
        } else {
          $this->assign('modules', $this->getModuleNavList());
        }
        $this->assign('displayType', $this->getModuleVar('display_type'));
        $this->assign('topItem', null);
        break;
        
     case 'search':
        $searchTerms = $this->getArg('filter');
        
        $federatedResults = array();
     
        foreach ($this->getNavigationModules(false) as $id => $info) {
          $module = self::factory($id);
          if ($module->getModuleVar('search')) {
            $results = array();
            $total = $module->federatedSearch($searchTerms, 2, $results);
            $federatedResults[] = array(
              'title'   => $info['title'],
              'results' => $results,
              'total'   => $total,
              'url'     => $module->urlForFederatedSearch($searchTerms),
            );
            unset($module);
          }
        }
        //error_log(print_r($federatedResults, true));
        $this->assign('federatedResults', $federatedResults);
        $this->assign('searchTerms',      $searchTerms);
        break;
    }
  }
}
