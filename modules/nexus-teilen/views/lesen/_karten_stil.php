.nsf-karten { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
.nsf-karte { display: flex; flex-direction: column; background: #fff; color: #1f2933; border-radius: 10px; overflow: hidden; text-decoration: none !important; box-shadow: 0 2px 14px rgba(11, 26, 46, .08); transition: transform .15s ease, box-shadow .15s ease; }
.nsf-karte:hover, .nsf-karte:focus { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(11, 26, 46, .14); }
.nsf-bild { display: block; aspect-ratio: 16 / 9; background: #0b1a2e center / cover no-repeat; }
.nsf-ohne-bild { display: flex; align-items: center; justify-content: center; font-size: 44px; background: linear-gradient(135deg, #0b1a2e, #1d3557); }
.nsf-inhalt { display: flex; flex-direction: column; gap: 6px; padding: 14px 16px 16px; flex: 1; }
.nsf-datum { font-size: 12px; color: #8a8f98; }
.nsf-titel { font-size: 17px; font-weight: 700; line-height: 1.3; color: #0b1a2e; }
.nsf-text { font-size: 14px; line-height: 1.5; color: #4b5563; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; }
.nsf-weiter { margin-top: auto; font-size: 14px; font-weight: 600; color: #b8941f; }
.nsf-bild.nsf-video { position: relative; }
.nsf-bild.nsf-video::after { content: "▶"; position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); width: 64px; height: 64px; border-radius: 50%; background: rgba(11, 26, 46, .7); color: #fff; font-size: 26px; line-height: 64px; text-align: center; padding-left: 4px; box-sizing: border-box; border: 2px solid #D4AF37; }
