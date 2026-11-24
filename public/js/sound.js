/* Notification sound, ported from Laravel 7 (playChime: 3 sine notes E5-A5-C#6 synthesised with
   WebAudio; no audio file). window.dmsSound.play() plays it when "Suara Notifikasi" is on
   (localStorage "dms_sound", default on like Laravel 7 "sound_enabled"); play(true) ignores the
   setting ("Tes suara"). Browsers only allow audio after a user gesture: nothing is created
   before the first click/key press, and play() before that stays silent. Never throws. */
(function () {
    var ctx, unlocked = false;
    var unlock = function () { unlocked = true; };
    addEventListener('pointerdown', unlock, true);
    addEventListener('keydown', unlock, true);

    window.dmsSound = {
        enabled: function () {
            try {
                var on = localStorage.getItem('dms_sound');
                return on ? on === '1' : localStorage.getItem('sound_enabled') !== 'false';
            } catch (e) { return true; }
        },
        play: function (force) {
            if (!unlocked || !(force || this.enabled())) { return; }
            try {
                ctx = ctx || new (window.AudioContext || window.webkitAudioContext)();
                ctx.resume(); /* no-op when running */
                var t0 = ctx.currentTime;
                /* [frequency, start, duration, volume] */
                [[659.25, 0, 0.22, 0.25], [880, 0.1, 0.32, 0.3], [1108.73, 0.22, 0.55, 0.35]].forEach(function (n) {
                    var o = ctx.createOscillator(), g = ctx.createGain(), t = t0 + n[1];
                    o.frequency.value = n[0];
                    g.gain.setValueAtTime(0.001, t);
                    g.gain.linearRampToValueAtTime(n[3], t + 0.02);
                    g.gain.exponentialRampToValueAtTime(0.0001, t + n[2]);
                    o.connect(g).connect(ctx.destination);
                    o.start(t);
                    o.stop(t + n[2]);
                });
            } catch (e) { /* no WebAudio or blocked: stay silent */ }
        }
    };
})();
