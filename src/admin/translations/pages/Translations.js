import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Type, Menu, FileText } from 'lucide-react';
import Tabs from '../components/Tabs';
import TranslationGrid from '../components/TranslationGrid';
import MenuTab from './MenuTab';
import Pages from './Pages';

const TABS = [
    { id: 'theme', label: 'Theme Strings', icon: Type },
    { id: 'pages', label: 'Pages', icon: FileText },
    { id: 'menu', label: 'Menu', icon: Menu },
];

export default function Translations() {
    const [ activeTab, setActiveTab ] = useState( 'theme' );

    return (
        <div className="p-6">
            <div className="mb-6">
                <h1 className="text-xl font-bold text-gray-900">
                    Snel <em className="font-serif font-normal italic">Translations</em>
                </h1>
                <p className="text-sm text-gray-500 mt-1">
                    { __( 'Manage all translations for your multilingual site', 'snel' ) }
                </p>
            </div>

            <Tabs tabs={ TABS } active={ activeTab } onChange={ setActiveTab } />

            { activeTab === 'theme' && <TranslationGrid dataKey="themeStrings" /> }
            { activeTab === 'pages' && <Pages /> }
            { activeTab === 'menu' && <MenuTab /> }
        </div>
    );
}
