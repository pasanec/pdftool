/**
 * @copyright Copyright (c) 2023 Immanuel Pasanec <i@pasanec.de>
 *
 * @author Immanuel Pasanec <i@pasanec.de>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
 import { generateFilePath } from '@nextcloud/router'

 import Vue from 'vue'
 import Merge from './Merge'
 
 // eslint-disable-next-line
 // __webpack_public_path__ = generateFilePath(appName, 'merge', 'js/')
 
//  const merger = document.createElement('div')
//  merger.setAttribute('id', 'pdftools-content')
 (function() {
	const PdftoolMultiselect = {
		attach(fileList) {
			fileList.registerMultiSelectFileAction({
				name: 'pdftool',
				displayName: t('pdftool', 'Pdf Merger'),
                permissions: OC.PERMISSION_READ, // Don't know if it's working
				iconClass: 'icon-pdf',
				order: 0,
				action: (selection) => {
                    console.info('PdfTool Multiselect action')
                    console.info(selection)
                    Vue.mixin({ methods: { t, n } })
 
                    new Vue({
                        el: '#pdftool-content',
                        render: h => h(Merge, {
                            props: {
                                files: selection,
                                filelistObj: fileList,
                            },
                        }),
                    })                 
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

	OC.Plugins.register('OCA.Files.FileList', PdftoolMultiselect)

    // const PdfToolSplitAction = {
    //     attach: function (menu) {
    //         menu.addMenuEntry({
    //             id: 'file',
    //             displayName: 'PdfTool',
    //             templateName: 'MyNewTemplate',
    //             iconClass: 'icon-pdf',
    //             fileType: 'file',
    //             actionHandler: function () {
    //                 console.log('PdfTool Menu clicked.');
    //             }
    //         });
    //     }
    // }
    // OC.Plugins.register('OCA.Files.NewFileMenu', PdfToolSplitAction);
    OCA.Files.fileActions.registerAction({
        mime: 'application/pdf',
        name: 'pdfToolSplitter',
        permissions: OC.PERMISSION_READ,
        iconClass: 'icon-pdf',
        // icon: OC.imagePath('gallery', 'gallery-dark'),
        actionHandler: (fileName, context) => {
            console.info('Split Action clicked')
            console.info(fileName)
            console.info(context.fileInfoModel.id)
        },
        displayName: t('pdftool', 'PDF Splitter')
    })


})()



//  Vue.mixin({ methods: { t, n } })
 
//  export default new Vue({
//      el: '#pdftools-content',
//      render: h => h(Merge),
//  })