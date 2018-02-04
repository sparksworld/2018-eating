<<<<<<< HEAD
// //logs.js

// Page({
// 	onLoad: function () {
// 		console.log('load')
// 		this.setData({
// 			'name_header.userName': wx.getStorageSync('props').val,
// 			'name_header.userHeader': wx.getStorageSync('props').avatar
// 		})
// 	},
// 	onShow: function() {
// 		console.log('show')
// 	},
// 	saveImage() {
// 		wx.saveImageToPhotosAlbum({
// 		    success: function(res) {
// 		    	console.log(res)
// 		    },
// 		    filePath:'../../assets/images/banner.png'
// 		})
// 	}
// })
var util = require('../../utils/methods.js');
=======
var util = require('../../utils/methods.js');
// var common = require('../../utils/common.js')
>>>>>>> master
Page({
    data: {
        windowWidth: 0,
        windowHeight: 0,
        contentHeight: 0,
<<<<<<< HEAD
        thinkList: [],
        footer: '',
        offset: 0,
        lineHeight: 30,
        content: '王小波的黄金时代有一片紫色的天空，天上飘着懒洋洋的云，他有好多奢望，想爱，想吃，想和陈清扬敦伟大的友谊。'
    },

    onLoad: function(options) {
        console.log(options)
        let that = this;
        this.setData({
			'name_header.userName': options.name,
			'name_header.userHeader': options.header
		})
        wx.getSystemInfo({
            success: function(res) {
                that.setData({
                    windowWidth: res.windowWidth,
                    windowHeight: res.windowHeight,
                    offset: (res.windowWidth - 300) / 2
                });
            }
        });
    },

    onShow: function() {
        this.getData()
    },

    getData: function() {
        let that = this;
        let i = 0;
        let lineNum = 1;
        let thinkStr = '';
        let thinkList = [];
        for (let item of that.data.content) {
            if (item === '\n') {
                thinkList.push(thinkStr);
                thinkList.push('a');
                i = 0;
                thinkStr = '';
                lineNum += 1;
            } else if (i === 19) {
                thinkList.push(thinkStr);
                i = 1;
                thinkStr = item;
                lineNum += 1;
            } else {
                thinkStr += item;
                i += 1;
            }
        }
        thinkList.push(thinkStr);
        that.setData({ thinkList: thinkList });
        that.createNewImg(lineNum);
    },

    drawSquare: function(ctx, height) {
        ctx.rect(0, 50, this.data.windowWidth, height);
        ctx.setFillStyle("#f5f6fd");
        ctx.fill()
    },

    drawFont: function(ctx, content, height) {
        ctx.setFontSize(16);
        ctx.setFillStyle("#484a3d");
        ctx.fillText(content, this.data.offset, height);
    },

    drawLine: function(ctx, height) {
        ctx.beginPath();
        ctx.moveTo(this.data.offset, height);
        ctx.lineTo(this.data.windowWidth - this.data.offset, height);
        ctx.stroke('#eee');
        ctx.closePath();
    },

    createNewImg: function(lineNum) {
        let that = this;
        let ctx = wx.createCanvasContext('myCanvas');
        let contentHeight = lineNum * that.data.lineHeight + 180;
        that.drawSquare(ctx, contentHeight);
        that.setData({ contentHeight: contentHeight });
        let height = 100;
        for (let item of that.data.thinkList) {
            if (item !== 'a') {
                that.drawFont(ctx, item, height);
                height += that.data.lineHeight;
            }
        }
        that.drawLine(ctx, lineNum * that.data.lineHeight + 120);
        that.drawFont(ctx, that.data.footer, lineNum * that.data.lineHeight + 156);
        // console.log(that.data.offset)
        ctx.drawImage(that.data.name_header.userHeader, that.data.windowWidth - that.data.offset - 50, lineNum * that.data.lineHeight + 125, 50, 50);
        that.drawFont(ctx, that.data.name_header.userName, lineNum * that.data.lineHeight + 156)
        ctx.draw();
    },

    savePic: function() {
        let that = this;
        wx.canvasToTempFilePath({
            x: 0,
            y: 50,
            width: that.data.windowWidth,
            height: that.data.contentHeight,
            canvasId: 'myCanvas',
            success: function(res) {
                util.savePicToAlbum(res.tempFilePath)
            }
        })
=======
        drawPicPath: ''
    },
    onLoad: function (options) {
        let that = this;
        this.setData({
            'name_header.userName': options.name,
            'name_header.userHeader': options.header
        })

        wx.getSystemInfo({
            success: function (res) {
                that.setData({
                    windowWidth: res.windowWidth,
                    windowHeight: res.windowHeight,
                });
            }
        });
        if (wx.getStorageSync('userName') && wx.getStorageSync('userName') != options.name) {
            wx.setStorageSync('activeIndex', this.randomNum(1, 18))
        } else {
            if (wx.getStorageSync('activeIndex')) {
                wx.setStorageSync('activeIndex', wx.getStorageSync('activeIndex'))
            } else {
                wx.setStorageSync('activeIndex', this.randomNum(1, 18))
            }
        }
        wx.setStorageSync('userName', options.name)
        var imgArr = []
        for (var i = 0; i < 19; i++) {
            imgArr.push('https://wq.chaoshuai.net/kaoshenmechifan/images/' + (i + 1) + '.jpg')
        }
        console.log(wx.getStorageSync('activeIndex'))
        this.setData({
            qrCode: '../assets/images/timg.png',
            isShowLoading: true,
            drawPicPath: imgArr[wx.getStorageSync('activeIndex')]
        })
    },


    randomNum(min, max) {
        let range = max - min;
        let rand = Math.random();
        let num = min + Math.round(rand * range);
        return num;
    },
    getData: function () {
        return new Promise((reslove, reject) => {
            wx.downloadFile({
                url: this.data.drawPicPath,
                success: function (res) {
                    reslove(res.tempFilePath)
                }.bind(this),
                fail: function () {
                    console.log('image load fail')
                }
            })
        })
    },
    imageLoad: function (e) {
        console.log('----------------->success')
        var _this = this
        var $width = e.detail.width,    //获取图片真实宽度
            $height = e.detail.height,
            ratio = $width / $height;    //图片的真实宽高比例
        var viewWidth = this.data.windowWidth,           //设置图片显示宽度
            viewHeight = this.data.windowWidth / ratio;    //计算的高度值
        this.getData().then(function (res) {
            let ctx = wx.createCanvasContext('myCanvas');
            _this.setData({
                contentHeight: viewHeight,
                contentHeight: viewHeight,
                isShowLoading: false
            })
            ctx.drawImage(res, 0, 0, viewWidth, _this.data.contentHeight);
            ctx.drawImage(_this.data.qrCode, viewWidth - viewWidth / 7.5 * 1.68, viewHeight - viewWidth / 7.5 * 1.35, viewWidth / 7, viewWidth / 7);
            _this.drawFont(ctx, _this.data.name_header.userName, viewHeight / 4)
            _this.drawFont(ctx, '点击图片,长按识别二维码', viewHeight / 1.1, viewWidth / 22, viewWidth / 7)
            _this.drawFont(ctx, '测测你2018年靠什么吃饭！', viewHeight / 1.05, viewWidth / 22, viewWidth / 8.5)
            ctx.draw()
        })
    },
    drawFont: function (ctx, content, height, font, a) {
        ctx.setFontSize(font || this.data.windowWidth / 23);
        ctx.setFillStyle("#25683f");
        ctx.setTextAlign('center')
        ctx.fillText(content, this.data.windowWidth / 2 - (a || 0), height);
    },
    savePic: function () {
        console.log(0)
        let that = this;
        console.log(wx.getStorageSync('saveImg') == that.data.name_header.userName)
        if (wx.getStorageSync('saveImg') == that.data.name_header.userName) {
            wx.showModal({
                title: '提示',
                content: '您已经保存过该图片，请去本地相册中查看',
                showCancel: false,
                success: function (res) {
                    if (res.confirm) {
                        console.log('用户点击确定')
                    } else if (res.cancel) {
                        console.log('用户点击取消')
                    }
                }
            })
        } else {
            wx.canvasToTempFilePath({
                width: that.data.windowWidth,
                height: that.data.contentHeight,
                canvasId: 'myCanvas',
                success: function (res) {
                    wx.setStorageSync('saveImg', that.data.name_header.userName)
                    util.savePicToAlbum(res.tempFilePath)
                }
            })
        }

    },
    onShareAppMessage: function (res) {
        if (res.from === 'button') {
            console.log(res.target)
        }
        return {
            title: '测一测你的2018吧',
            path: 'pages/index/index',
            success: function (res) {
                // 转发成功
            },
            fail: function (res) {
                // 转发失败
            }
        }
>>>>>>> master
    }
});