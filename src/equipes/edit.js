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

import { InspectorControls, BlockControls, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl} from '@wordpress/components';

// https://github.com/bacoords/example-image-block/blob/main/src/edit.js
import { MediaToolbar, Image } from '@10up/block-components';
import AttachmentImage from './AttachmentImage';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({attributes, setAttributes}) {
    const {picture, rank, name} = attributes;
    const handlePictureSelect = (image) => {setAttributes({picture: image.id});}
    const handlePictureRemove = () => {setAttributes({picture: null});};

    return (
       <div { ...useBlockProps()}>
            <InspectorControls>
                <PanelBody title={__('Paramétrage du bloc', 'equipes')}>
                        <Image 
                            id={picture}
                            size="full"
                            onSelect={handlePictureSelect}
                            labels={{
                                title: "Séléctionnez un portrait",
                                instructions: "Séléctionner une image pour illustrer les conseillers et conseillères municipaux et municipales"
                            }}
                        />
                        <Button 
                            isDestructive 
                            variant='link'
                            onClick={handlePictureRemove}
                        >Supprimer l'image mise en ligne
                        </Button>
                    <hr />
                    <TextControl
                        label={"Nom de l'élu.e"}
                        value={name}
                        onChange={(value) => setAttributes({name: value})}
                        type="text"
                    />
                    <TextControl
                        label={"Fonction de l'élu.e"}
                        value={rank}
                        onChange={(value) => setAttributes({rank: value})}
                        type="text"
                    />
                </PanelBody>
            </InspectorControls>
            <BlockControls>
                <MediaToolbar 
                    isOptional
                    id={picture}
                    labels={{
                        add: "Ajouter une image",
                        remove: "Supprimer l'image",
                        replace: "Remplacer l'image"
                    }}
                    onSelect={handlePictureSelect}
                    onRemove={handlePictureRemove}
                    />
            </BlockControls>

            {/* Lorsque le component Image possède une image mise en ligne, on le cache pour laisser place au component prévue à la bonne mise en forme */}
            {!picture && (   
            <Image 
                id={picture}
                size="full"
                onSelect={handlePictureSelect}
                labels={{
                    title: "Séléctionnez un portrait",
                    instructions: "Séléctionner une image pour illustrer les conseillers et conseillères municipaux et municipales"
                }}
            />)}

            <div className='equipes-card'>
                {name && (<h4>{name}</h4>)}
                {rank && (<p>{rank}</p>)}
                {picture && (<AttachmentImage imageId={picture} />)}
            </div>
       </div>
    );
}