var util = require('../../utils/methods.js');
// var common = require('../../utils/common.js')
Page({
    data: {
        windowWidth: 0,
        windowHeight: 0,
        contentHeight: 0,
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
    }
});