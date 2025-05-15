/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useState } from 'react';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, RadioControl } from '@wordpress/components';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({attributes, setAttributes}) {
    let show = false; //Cette variable permet de gérer l'affichage des attributs tags & categorie
    const { orderType, click, displayDate, displayContent, displayTitle, displayCategories, displayTags} = attributes;
    const [checked, setChecked] = useState(orderType);

    const orderTypehandler = (value) => {
        setChecked(value);
        setAttributes({orderType: value})
    }

    {if (displayDate || displayTitle || displayContent) {
        show = true;
    } else {
        show = false;
    }}

    // L'attribut n'apparaitras pas dans le render.php si il n'est pas non plus présent dans le Block.json
    return (
       <>
        <InspectorControls>
            <PanelBody title={__('Paramétrage du bloc', 'touslesarticles')}>
                <RadioControl
                    label={"Ordre d'affichage des articles"}
                    selected={checked}
                    options={[
                        {label: "Ascendant", value: "ASC"},
                        {label: "Descendant", value: "DESC"},
                    ]}
                    onChange={(value) => orderTypehandler(value)}
                />
                <ToggleControl 
                    checked={!! click}
                    label={__('Rendre l\'article cliquable', 'touslesarticles')}
                    onChange={() => setAttributes({click: ! click})}
                />
                <ToggleControl 
                    checked={!! displayDate}
                    label={__('Afficher la date', 'touslesarticles')}
                    onChange={() => setAttributes({displayDate: ! displayDate})}
                />
                <ToggleControl 
                    checked={!! displayContent}
                    label={__('Afficher le contenu', 'touslesarticles')}
                    onChange={() => setAttributes({displayContent: ! displayContent})}
                />
                <ToggleControl 
                    checked={!! displayTitle}
                    label={__('Afficher le titre', 'touslesarticles')}
                    onChange={() => setAttributes({displayTitle: ! displayTitle})}
                />
                {show && (
                    <>
                        <hr />
                        <ToggleControl 
                            checked={!! displayCategories}
                            label={__('Afficher les étiquettes', 'touslesarticles')}
                            onChange={(value) => setAttributes({displayCategories: value})}
                        />
                        <ToggleControl 
                            checked={!! displayTags}
                            label={__('Afficher les catégories', 'touslesarticles')}
                            onChange={(value) => setAttributes({displayTags: value})}
                        />
                    </>   
                )}
            </PanelBody>
        </InspectorControls>
        <p { ...useBlockProps() }><h3>Afficher tous vos articles et plus ..</h3></p>
       </>
    );
}