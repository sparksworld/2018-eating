//index.js
//获取应用实例
let common = require('../../utils/common.js')
const app = getApp()
Page({
    data: {
        timer: null,
        userInfo: {},
        animationData: {},
        pageData: {
            title: '2018，你靠什么吃饭？',
            imgBanner: {
                path: '../assets/images/banner.jpg'
            }
        },
        bottomPic: '../assets/images/timg.png'
    },
    onLoad() {
        var that = this
<<<<<<< HEAD
        app.getUserInfo(function(userInfo) {
=======
        app.getUserInfo(function (userInfo) {
>>>>>>> master
            //更新数据
            console.log(userInfo)
            that.setData({
                userInfo: userInfo
            })
        })
    },
    testTest(e) {
        wx.navigateTo({
            url: `../reault/reault?name=${e.detail.value.username}&header=${this.data.userInfo.avatarUrl}`
        })
    },
<<<<<<< HEAD
    showAnimated: function () {
        var t = this;
        0 === this.data.currentIndex ? (setTimeout(function () {
            t.setData({
                one_one: "animated fadeIn",
                one_two: "animated bounceIn"
            })
        }, 1e3), 
        setTimeout(function () {
            t.setData({
                one_three: "animated bounceIn"
            })
        }, 1500), 
        setTimeout(function () {
            t.setData({
                one_four: "animated bounceIn"
            })
        }, 1800), 
        setTimeout(function () {
            t.setData({
                one_five: "animated lightSpeedIn"
            })
        }, 1900)) : 1 === this.data.currentIndex && (setTimeout(function () {
            t.setData({
                two_one: "animated fadeInDown",
                two_two: "animated fadeInUp"
            })
        }, 1e3), 
        setTimeout(function () {
            t.setData({
                two_three: "animated zoomIn",
                two_four: "animated zoomIn"
            })
        }, 1200), 
        setTimeout(function () {
            t.setData({
                two_three: "two-music-one",
                two_four: "two-music-two"
            })
        }, 2200))
=======
    lengthInfo: function (e) {
        var _this = this
        if (e.detail.value.length >= 12) {
            wx.showModal({
                title: '提示',
                content: '名字最大长度不能超过20个字符',
                showCancel: false,
                success: function (res) {
                    if (res.confirm) {
                        _this.setData({
                            "userInfo.nickName": e.detail.value.substr(0, 12)
                        })
                    }
                }
            })
        }
>>>>>>> master
    },
    // judgeLength() {
    //     return this.data.userInfo.nickName.length >= 12 ? this.data.userInfo.nickName.substr(0, 12) : this.data.userInfo.nickName
    // },
    // onHide: function() {
    //     common.showLoading(false)
    //     // clearTimeout(this.data.timer);
    // },
    // //事件处理函数
    // testTest: function() {
    //     common.showLoading(true)
    //     if (!this.data.props.status) {
    //         this.setData({
    //             'props.val': this.judgeLength(),
    //             'props.avatar': this.data.userInfo.avatarUrl
    //         })
    //         wx.setStorageSync('props', this.data.props)
    //     }
    //     wx.navigateTo({
    //         url: '../reault/reault'
    //     })
    // },
    // getName: function(e) {
    //     if (e.detail.value.length >= 10) {
    //         wx.showModal({
    //             title: '提示',
    //             content: '名字最大长度不能超过12个字符',
    //             showCancel: false,
    //             success: function(res) {
    //                 if (res.confirm) {
    //                     e.detail.value = e.detail.value.substr(0, 12)
    //                 }
    //             }
    //         })
    //     } else {
    //         this.setData({
    //             'props.status': 1
    //         })
    //         if (e.detail.value) {
    //             this.setData({
    //                 'props.val': e.detail.value,
    //                 'props.avatar': this.data.userInfo.avatarUrl
    //             })
    //         } else {
    //             this.setData({
    //                 'props.val': this.judgeLength(),
    //                 'props.avatar': this.data.userInfo.avatarUrl
    //             })
    //         }
    //         wx.setStorageSync('props', this.data.props)
    //     }
    // },
    // onLoad: function() {
    // 	app.getUserInfo(function(userInfo) {
    //         //更新数据
    //         this.setData({
    //             userInfo: userInfo
    //         })
    //     }.bind(this))        
    // },
<<<<<<< HEAD
    // onShareAppMessage: function(res) {
    //     if (res.from === 'button') {
    //         console.log(res.target)
    //     }
    //     return {
    //         title: '测一测你的2018吧',
    //         path: 'pages/index/index',
    //         success: function(res) {
    //             // 转发成功
    //         },
    //         fail: function(res) {
    //             // 转发失败
    //         }
    //     }
    // }
=======
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
>>>>>>> master
})