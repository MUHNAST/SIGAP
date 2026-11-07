import './bootstrap';
import 'flowbite';
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
/*
|--------------------------------------------------------------------------
| jQuery
|--------------------------------------------------------------------------
*/
import $ from 'jquery';
window.$ = window.jQuery = $;
/*
|--------------------------------------------------------------------------
| GLOBAL CSS
|--------------------------------------------------------------------------
*/
import '@fortawesome/fontawesome-free/css/all.min.css';
import '@fontsource/inter/300.css';
import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/600.css';
import '@fontsource/inter/700.css';
import '@fontsource/inter/800.css';
/*
|--------------------------------------------------------------------------
| CORE
|--------------------------------------------------------------------------
*/
import {
    initActivityFilter
} from './components/core/activity-filter';
import {
    initAutoFocus
} from './components/core/auto-focus';
import {
    initChart,
    resizeChart
} from './components/core/chart';
import {
    initDataTablesAuto
} from './components/core/datatable';
import {
    initDropdown
} from './components/core/dropdown';
import {
    initModal
} from './components/core/modal';
import {
    initNotification
} from './components/core/notification';
import {
    initSidebar
} from './components/core/sidebar';
import './components/kgb/kgb-print';
import {
    initEmployeeExport
} from './components/core/employee-export';
import { initDocumentUploads } from './components/core/document-upload';
/*
|--------------------------------------------------------------------------
| FORM
|--------------------------------------------------------------------------
*/
import {
    initEmployeeEducation
} from './components/form/initEmployeeEducation';
import {
    initEmployeeRank
} from './components/form/employee-rank';
import {
    initEmployeeSelect
} from './components/form/employee-select';
import {
    initEmployeeType
} from './components/form/employee-type';
import {
    initLocationForm
} from './components/form/location-form';
/*
|--------------------------------------------------------------------------
| KGB
|--------------------------------------------------------------------------
*/
import {
    initKgbEditable
} from './components/kgb/kgb-editable';
/*
|--------------------------------------------------------------------------
| MODULES
|--------------------------------------------------------------------------
*/
import {
    initImagePreview
} from './components/modules/photo-preview';
import {
    initModalValidation
} from './components/modules/modal-validation';
import {
    initPasswordToggle
} from './components/modules/password-toggle';
import {
    initSettingTabs
} from './components/modules/settings-tab';
import {
    initWelcomeCard
} from './components/modules/welcome-card';
import {
    initRolePermissions
} from './components/modules/role-permissions';

/*
|--------------------------------------------------------------------------
| INIT
|--------------------------------------------------------------------------
*/
document.addEventListener(
    'DOMContentLoaded',
    async () => {
        /*
        |--------------------------------------------------------------------------
        | FORM PEGAWAI
        |--------------------------------------------------------------------------
        |
        | Urutan penting:
        |
        | 1. Status pegawai
        | 2. Pendidikan
        | 3. Pangkat
        |
        | Status menentukan PNS/PPPK.
        | Pendidikan kemudian disaring berdasarkan status.
        | Setelah itu pangkat dimuat berdasarkan pendidikan.
        |
        */
        initEmployeeType();
        initEmployeeEducation();
        initEmployeeRank();
        /*
        |--------------------------------------------------------------------------
        | FORM LAINNYA
        |--------------------------------------------------------------------------
        */
        await initLocationForm();
        await initEmployeeSelect();
        /*
        |--------------------------------------------------------------------------
        | CORE
        |--------------------------------------------------------------------------
        */
        initActivityFilter();
        initPasswordToggle();
        initAutoFocus();
        initChart();
        initDataTablesAuto();
        initModal();
        initDropdown();
        initNotification();
        initSidebar();
        initEmployeeExport();
        // updateColumnCount();
        initDocumentUploads();
        /*
        |--------------------------------------------------------------------------
        | MODULES
        |--------------------------------------------------------------------------
        */
        initImagePreview();
        initSettingTabs();
        initModalValidation();
        initKgbEditable();
        initWelcomeCard();
        initRolePermissions();
    }
);
/*
|--------------------------------------------------------------------------
| RESIZE
|--------------------------------------------------------------------------
*/
window.addEventListener(
    'resize',
    () => {
        clearTimeout(
            window.chartResizeTimer
        );
        window.chartResizeTimer =
            setTimeout(() => {
                resizeChart();
            }, 150);
    }
);
