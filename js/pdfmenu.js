/**
 *
 * @copyright Copyright (c) 2022, Immanuel Pasanec (immanuel@pasanec.de)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */
var myFileMenuPlugin = {
    attach: function (menu) {
        menu.addMenuEntry({
            id: 'file',
            displayName: 'PdfTool',
            templateName: 'MyNewTemplate',
            iconClass: 'icon-filetype-text',
            fileType: 'file',
            actionHandler: function () {
                console.log('PdfTool Menu clicked.');
            }
        });
    }
};
OC.Plugins.register('OCA.Files.NewFileMenu', myFileMenuPlugin);
OCA.Files.fileActions.registerAction({
    mime: 'application/pdf',
    name: 'PdfTool',
    permissions: OC.PERMISSION_READ,
    iconClass: 'icon-filetype-text',
    // icon: OC.imagePath('gallery', 'gallery-dark'),
    actionHandler: (fileName, context) => {
        console.info('Action clicked')
        console.info(fileName)
        console.info(context.fileInfoModel.id)
    },
    displayName: 'PDF Tool'
})
;


(function() {
	const FilesPlugin = {
		attach(fileList) {
			fileList.registerMultiSelectFileAction({
				name: 'pdftool',
				displayName: 'Pdf Merger',
                permissions: OC.PERMISSION_READ, // Don't know if it's working
				iconClass: 'mime-pdf',
				order: 0,
				action: (files) => {
                    console.info('PdfTool Multiselect action')
                    console.info(files)
				},
			})

			fileList.$el.on('fileActionsReady', data => {
				console.info('fileActionsReady')
                console.info(fileList)
                // fileList.fileMultiSelectMenu.toggleItemVisibility('pdftool', true)
				// console.info('fileActionsReady')
			})
			fileList.$el.on('afterChangeDirectory', data => {
                console.info('afterChangeDirectory')
                fileList.fileMultiSelectMenu.toggleItemVisibility('pdftool', true)
                console.info(fileList)
                // console.info(data)
			})
            fileList.$el.on('change', data => {
                console.info('PdfTool Multiselect change')
                console.info(fileList.getSelectedFiles())
                let showMultiselectAction = false
                if (fileList.getSelectedFiles().length > 1) {
                showMultiselectAction = true
                    fileList.getSelectedFiles().forEach(selection => {
                            console.info('selection.mimetype', selection.mimetype)
                            if (selection.mimetype !== 'application/pdf') {
                            console.info('selection.mimetype if TRUE', selection.mimetype)
                            showMultiselectAction = false
                        }
                    });
                }
                fileList.fileMultiSelectMenu.toggleItemVisibility('pdftool', showMultiselectAction)
            })
		},
	}

	OC.Plugins.register('OCA.Files.FileList', FilesPlugin)
})()

console.info('ASDFASDFASDFASDFASDF')
const evil = document.querySelector(':root')
evil.style.setProperty('--icon-mime-pdf', 'url(' + OC.generateUrl('/apps/theming/img/core/filetypes/application-pdf.svg?v=0') + ')')
document.querySelector('.icon.mime-pdf').style.setProperty('background-image', 'url(' + OC.generateUrl('/apps/theming/img/core/filetypes/application-pdf.svg?v=0') + ')' )