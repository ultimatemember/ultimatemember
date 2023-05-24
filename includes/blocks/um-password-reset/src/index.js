import { registerBlockType } from '@wordpress/blocks';
import ServerSideRender from '@wordpress/server-side-render';
import { useBlockProps } from '@wordpress/block-editor';

registerBlockType('um-block/um-password-reset', {
	edit: function (props) {
		const blockProps = useBlockProps();

		return (
			<div {...blockProps}>
				<ServerSideRender block="um-block/um-password-reset" />
			</div>
		);

	},
	save: function save(props) {
		return null;
	}
});
