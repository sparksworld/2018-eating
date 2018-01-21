Component({
    name: 'indexAni',
    data: {
        currentIndex: 0,
        oldIndex: 0,
        view: [{ in: "",
            out: ""
        }, { in: "",
            out: ""
        }]
    },
    attached: function () {
        console.log(this)
        // this.showAnimated();
        // showAnimated()
        var t = this;
        setTimeout(function () {
            t.setData({
                bottom: "animated slideInUp"
            })
        }, 1000), 
        setTimeout(function () {
            t.setData({
                bottom_one: "animated slideInUp"
            })
        }, 1100), 
        setTimeout(function () {
            t.setData({
                bottom_two: "animated slideInUp"
            })
        }, 1200), 
        setTimeout(function () {
            t.setData({
                bottom_three: "animated slideInUp"
            })
        }, 1300), 
        setTimeout(function () {
            t.setData({
                bottom_four: "animated slideInUp"
            })
        }, 1400), 
        setTimeout(function () {
            t.setData({
                bottom_one: "bottom-4s-move"
            })
        }, 2100), 
        setTimeout(function () {
            t.setData({
                bottom_two: "bottom-3s-move"
            })
        }, 2200), 
        setTimeout(function () {
            t.setData({
                bottom_three: "bottom-2s-move"
            })
        }, 2300), 
        setTimeout(function () {
            t.setData({
                bottom_four: "bottom-1s-move"
            })
        }, 2400)
    }
})