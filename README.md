# i244_projekt

Tellimuse halduse lahendus piiratud skoobis.

Lahendus on mõeldud toimima rollipõhiselt. Kasutusel on 4 erinevat rolli:
 - 	admin
	user: tonutoru
	passw: toru)
 - klient	
	user: hanneshapu
	passw: hapu
 - tellimusehaldur
	user: markomoru
	passw: moru
 - laotöötaja
	user: karmokibe
	passw: kibe

Protsessid ja rollid:
 - sisselogimine --> kõik rollid
 - uue tellimuse lisamine --> klient
 - tellimuse kinnitamine --> tellimusehaldur
 - komplekteerimine --> laotöötaja
 
Rollide vaated:
 - klient
	* saab lisada uusi tellimusi
	* saab vaadata täitamata tellimusi
	* saab vaadata komplekteeritud tellimusi
- admin
	* saab vaadata ootel tellimusi ja neid kinnitada
	* saab vaadata komplekteerimises olevaid tellimusi
	* saab vaadata komplekteeritud tellimusi
- tellimusehaldur
	* saab vaadata kliendi lisatud tellimusi ja neid kinnitada (s.t komplekteerimisele saata)
	* saab vaadata komplekteeritud tellimusi
- laotöötaja
	* saab vaadata tellimusehalduri poolt kinnitatud e. komplekteerimisele saadetud tellimusi
 
