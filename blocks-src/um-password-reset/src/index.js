import { registerBlockType } from '@wordpress/blocks';
import ServerSideRender from '@wordpress/server-side-render';
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';

registerBlockType('um-block/um-password-reset', {
	edit: function () {
		const blockProps = useBlockProps();

		return (
			<div {...blockProps}>
				<Disabled>
					<ServerSideRender block="um-block/um-password-reset" />
				</Disabled>
			</div>
		);
	},

	save: () => null
});
