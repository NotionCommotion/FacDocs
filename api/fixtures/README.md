Need to be alphabetically ordered so that entities with FK requirements are first?
i.e. SystemUser needs to be before SystemOrganization?

# Be careful about regex. related_dummy_ will match related_dummy_ulid_ and cause errors!!!
App\Entity\RelatedDummy:
    related_dummy_{1..10}:
        name: <name()>
App\Entity\RelatedDummyUlid:
    related_dummy_ulid_{1..10}:
        name: <name()>


Symfony: https://github.com/theofidry/AliceBundle
Main: https://github.com/nelmio/alice

#######################  List of formatters per https://fakerphp.github.io/formatters/  #################

$faker = Faker\Factory::create();
Faker\Provider\en_US\Person#

title($gender = null|'male'|'female')     // 'Ms.'
titleMale()                               // 'Mr.'
titleFemale()                             // 'Ms.'
suffix()                                  // 'Jr.'
name($gender = null|'male'|'female')      // 'Dr. Zane Stroman'
firstName($gender = null|'male'|'female') // 'Maynard'
firstNameMale()                           // 'Maynard'
firstNameFemale()                         // 'Rachel'
lastName()                                // 'Zulauf'
Faker\Provider\en_US\Address#

cityPrefix()                       // 'Lake'
secondaryAddress()                 // 'Suite 961'
state()                            // 'NewMexico'
stateAbbr()                        // 'OH'
citySuffix()                       // 'borough'
streetSuffix()                     // 'Keys'
buildingNumber()                   // '484'
city()                             // 'West Judge'
streetName()                       // 'Keegan Trail'
streetAddress()                    // '439 Karley Loaf Suite 897'
postcode()                         // '17916'
address()                          // '8888 Cummings Vista Apt. 101, Susanbury, NY 95473'
country()                          // 'Falkland Islands (Malvinas)'
latitude($min = -90, $max = 90)    // 77.147489
longitude($min = -180, $max = 180) // 86.211205
Faker\Provider\en_US\PhoneNumber#

phoneNumber()              // '827-986-5852'
phoneNumberWithExtension() // '201-886-0269 x3767'
tollFreePhoneNumber()      // '(888) 937-7238'
e164PhoneNumber()          // '+27113456789'
Faker\Provider\en_US\Company#

catchPhrase()   // 'Monitored regional contingency'
bs()            // 'e-enable robust architectures'
company()       // 'Bogan-Treutel'
companySuffix() // 'and Sons'
jobTitle()      // 'Cashier'
Faker\Provider\en_US\Text#

realText($maxNbChars = 200, $indexSize = 2)
// "And yet I wish you could manage it?) 'And what are they made of?' Alice asked in a shrill, passionate voice. 'Would YOU like cats if you were never even spoke to Time!' 'Perhaps not,' Alice replied."
realTextBetween($minNbChars = 160, $maxNbChars = 200, $indexSize = 2)
// "VERY short remarks, and she ran across the garden, and I had not long to doubt,
#######################  END LIST OF FORMATTERS #############