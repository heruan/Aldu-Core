<?php
/**
 * Pluralize and singularize English words.
 *
 * Used by Cake's naming conventions throughout the framework.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Aldu\Core\Utility
 * @since         AlduPHP(tm) v1.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Aldu\Core\Utility;
/**
 * Pluralize and singularize English words.
 *
 * Inflector pluralizes and singularizes English nouns.
 * Used by Cake's naming conventions throughout the framework.
 *
 * @package       cake.libs
 * @link          http://book.cakephp.org/view/1478/Inflector
 */
class Inflector
{

  /**
   * Plural inflector rules
   *
   * @var array
   */
  protected static $_plural = array(
    'rules' => array(
      '/(s)tatus$/i' => '\1\2tatuses',
      '/(quiz)$/i' => '\1zes',
      '/^(ox)$/i' => '\1\2en',
      '/([m|l])ouse$/i' => '\1ice',
      '/(matr|vert|ind)(ix|ex)$/i' => '\1ices',
      '/(x|ch|ss|sh)$/i' => '\1es',
      '/([^aeiouy]|qu)y$/i' => '\1ies',
      '/(hive)$/i' => '\1s',
      '/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
      '/sis$/i' => 'ses',
      '/([ti])um$/i' => '\1a',
      '/(p)erson$/i' => '\1eople',
      '/(m)an$/i' => '\1en',
      '/(c)hild$/i' => '\1hildren',
      '/(buffal|tomat)o$/i' => '\1\2oes',
      '/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|vir)us$/i' => '\1i',
      '/us$/i' => 'uses',
      '/(alias)$/i' => '\1es',
      '/(ax|cris|test)is$/i' => '\1es',
      '/s$/' => 's',
      '/^$/' => '',
      '/$/' => 's',
    ),
    'uninflected' => array(
      '.*[nrlm]ese',
      '.*deer',
      '.*fish',
      '.*measles',
      '.*ois',
      '.*pox',
      '.*sheep',
      'people'
    ),
    'irregular' => array(
      'atlas' => 'atlases',
      'beef' => 'beefs',
      'brother' => 'brothers',
      'child' => 'children',
      'corpus' => 'corpuses',
      'cow' => 'cows',
      'ganglion' => 'ganglions',
      'genie' => 'genies',
      'genus' => 'genera',
      'graffito' => 'graffiti',
      'hoof' => 'hoofs',
      'loaf' => 'loaves',
      'man' => 'men',
      'money' => 'monies',
      'mongoose' => 'mongooses',
      'move' => 'moves',
      'mythos' => 'mythoi',
      'niche' => 'niches',
      'numen' => 'numina',
      'occiput' => 'occiputs',
      'octopus' => 'octopuses',
      'opus' => 'opuses',
      'ox' => 'oxen',
      'penis' => 'penises',
      'person' => 'people',
      'sex' => 'sexes',
      'soliloquy' => 'soliloquies',
      'testis' => 'testes',
      'trilby' => 'trilbys',
      'turf' => 'turfs'
    )
  );

  /**
   * Singular inflector rules
   *
   * @var array
   */
  protected static $_singular = array(
    'rules' => array(
      '/(s)tatuses$/i' => '\1\2tatus',
      '/^(.*)(menu)s$/i' => '\1\2',
      '/(quiz)zes$/i' => '\\1',
      '/(matr)ices$/i' => '\1ix',
      '/(vert|ind)ices$/i' => '\1ex',
      '/^(ox)en/i' => '\1',
      '/(alias)(es)*$/i' => '\1',
      '/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|viri?)i$/i' => '\1us',
      '/([ftw]ax)es/i' => '\1',
      '/(cris|ax|test)es$/i' => '\1is',
      '/(shoe|slave)s$/i' => '\1',
      '/(o)es$/i' => '\1',
      '/ouses$/' => 'ouse',
      '/([^a])uses$/' => '\1us',
      '/([m|l])ice$/i' => '\1ouse',
      '/(x|ch|ss|sh)es$/i' => '\1',
      '/(m)ovies$/i' => '\1\2ovie',
      '/(s)eries$/i' => '\1\2eries',
      '/([^aeiouy]|qu)ies$/i' => '\1y',
      '/([lr])ves$/i' => '\1f',
      '/(tive)s$/i' => '\1',
      '/(hive)s$/i' => '\1',
      '/(drive)s$/i' => '\1',
      '/([^fo])ves$/i' => '\1fe',
      '/(^analy)ses$/i' => '\1sis',
      '/(analy|ba|diagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
      '/([ti])a$/i' => '\1um',
      '/(p)eople$/i' => '\1\2erson',
      '/(m)en$/i' => '\1an',
      '/(c)hildren$/i' => '\1\2hild',
      '/(n)ews$/i' => '\1\2ews',
      '/eaus$/' => 'eau',
      '/^(.*us)$/' => '\\1',
      '/s$/i' => ''
    ),
    'uninflected' => array(
      '.*[nrlm]ese',
      '.*deer',
      '.*fish',
      '.*measles',
      '.*ois',
      '.*pox',
      '.*sheep',
      '.*ss'
    ),
    'irregular' => array(
      'waves' => 'wave'
    )
  );

  /**
   * Words that should not be inflected
   *
   * @var array
   */
  protected static $_uninflected = array(
    'Amoyese',
    'bison',
    'Borghese',
    'bream',
    'breeches',
    'britches',
    'buffalo',
    'cantus',
    'carp',
    'chassis',
    'clippers',
    'cod',
    'coitus',
    'Congoese',
    'contretemps',
    'corps',
    'debris',
    'diabetes',
    'djinn',
    'eland',
    'elk',
    'equipment',
    'Faroese',
    'flounder',
    'Foochowese',
    'gallows',
    'Genevese',
    'Genoese',
    'Gilbertese',
    'graffiti',
    'headquarters',
    'herpes',
    'hijinks',
    'Hottentotese',
    'information',
    'innings',
    'jackanapes',
    'Kiplingese',
    'Kongoese',
    'Lucchese',
    'mackerel',
    'Maltese',
    'media',
    'mews',
    'moose',
    'mumps',
    'Nankingese',
    'news',
    'nexus',
    'Niasese',
    'Pekingese',
    'Piedmontese',
    'pincers',
    'Pistoiese',
    'pliers',
    'Portuguese',
    'proceedings',
    'rabies',
    'rice',
    'rhinoceros',
    'salmon',
    'Sarawakese',
    'scissors',
    'sea[- ]bass',
    'series',
    'Shavese',
    'shears',
    'siemens',
    'species',
    'swine',
    'testes',
    'trousers',
    'trout',
    'tuna',
    'Vermontese',
    'Wenchowese',
    'whiting',
    'wildebeest',
    'Yengeese'
  );

  /**
   * Irregular English verbs
   *
   * @var array
   */
  protected static $_irregularVerbs = array(
    'arise' => array(
      0 => 'arose',
      1 => 'arisen',
    ),
    'awake' => array(
      0 => 'awakened',
      1 => 'awakened',
    ),
    'backslide' => array(
      0 => 'backslid',
      1 => 'backslidden',
    ),
    'be' => array(
      0 => 'was',
      1 => 'been',
    ),
    'bear' => array(
      0 => 'bore',
      1 => 'born',
    ),
    'beat' => array(
      0 => 'beat',
      1 => 'beaten',
    ),
    'become' => array(
      0 => 'became',
      1 => 'become',
    ),
    'begin' => array(
      0 => 'began',
      1 => 'begun',
    ),
    'bend' => array(
      0 => 'bent',
      1 => 'bent',
    ),
    'bet' => array(
      0 => 'bet',
      1 => 'bet',
    ),
    'bid' => array(
      0 => 'bid',
      1 => 'bid',
    ),
    'bind' => array(
      0 => 'bound',
      1 => 'bound',
    ),
    'bite' => array(
      0 => 'bit',
      1 => 'bitten',
    ),
    'bleed' => array(
      0 => 'bled',
      1 => 'bled',
    ),
    'blow' => array(
      0 => 'blew',
      1 => 'blown',
    ),
    'break' => array(
      0 => 'broke',
      1 => 'broken',
    ),
    'breed' => array(
      0 => 'bred',
      1 => 'bred',
    ),
    'bring' => array(
      0 => 'brought',
      1 => 'brought',
    ),
    'broadcast' => array(
      0 => 'broadcast',
      1 => 'broadcast',
    ),
    'browbeat' => array(
      0 => 'browbeat',
      1 => 'browbeaten',
    ),
    'build' => array(
      0 => 'built',
      1 => 'built',
    ),
    'burn' => array(
      0 => 'burned',
      1 => 'burned',
    ),
    'burst' => array(
      0 => 'burst',
      1 => 'burst',
    ),
    'bust' => array(
      0 => 'busted',
      1 => 'busted',
    ),
    'buy' => array(
      0 => 'bought',
      1 => 'bought',
    ),
    'cast' => array(
      0 => 'cast',
      1 => 'cast',
    ),
    'catch' => array(
      0 => 'caught',
      1 => 'caught',
    ),
    'choose' => array(
      0 => 'chose',
      1 => 'chosen',
    ),
    'cling' => array(
      0 => 'clung',
      1 => 'clung',
    ),
    'clothe' => array(
      0 => 'clothed',
      1 => 'clothed',
    ),
    'come' => array(
      0 => 'came',
      1 => 'come',
    ),
    'cost' => array(
      0 => 'cost',
      1 => 'cost',
    ),
    'creep' => array(
      0 => 'crept',
      1 => 'crept',
    ),
    'crossbreed' => array(
      0 => 'crossbred',
      1 => 'crossbred',
    ),
    'cut' => array(
      0 => 'cut',
      1 => 'cut',
    ),
    'daydream' => array(
      0 => 'daydreamed',
      1 => 'daydreamed',
    ),
    'deal' => array(
      0 => 'dealt',
      1 => 'dealt',
    ),
    'dig' => array(
      0 => 'dug',
      1 => 'dug',
    ),
    'disprove' => array(
      0 => 'disproved',
      1 => 'disproved',
    ),
    'dive' => array(
      0 => 'dived',
      1 => 'dived',
    ),
    'do' => array(
      0 => 'did',
      1 => 'done',
    ),
    'draw' => array(
      0 => 'drew',
      1 => 'drawn',
    ),
    'dream' => array(
      0 => 'dreamed',
      1 => 'dreamed',
    ),
    'drink' => array(
      0 => 'drank',
      1 => 'drunk',
    ),
    'drive' => array(
      0 => 'drove',
      1 => 'driven',
    ),
    'dwell' => array(
      0 => 'dwelt',
      1 => 'dwelt',
    ),
    'eat' => array(
      0 => 'ate',
      1 => 'eaten',
    ),
    'fall' => array(
      0 => 'fell',
      1 => 'fallen',
    ),
    'feed' => array(
      0 => 'fed',
      1 => 'fed',
    ),
    'feel' => array(
      0 => 'felt',
      1 => 'felt',
    ),
    'fight' => array(
      0 => 'fought',
      1 => 'fought',
    ),
    'find' => array(
      0 => 'found',
      1 => 'found',
    ),
    'fit' => array(
      0 => 'fit',
      1 => 'fit',
    ),
    'flee' => array(
      0 => 'fled',
      1 => 'fled',
    ),
    'fling' => array(
      0 => 'flung',
      1 => 'flung',
    ),
    'fly' => array(
      0 => 'flew',
      1 => 'flown',
    ),
    'forbid' => array(
      0 => 'forbade',
      1 => 'forbidden',
    ),
    'forecast' => array(
      0 => 'forecast',
      1 => 'forecast',
    ),
    'forego' => array(
      0 => 'forewent',
      1 => 'foregone',
    ),
    'foresee' => array(
      0 => 'foresaw',
      1 => 'foreseen',
    ),
    'foretell' => array(
      0 => 'foretold',
      1 => 'foretold',
    ),
    'forget' => array(
      0 => 'forgot',
      1 => 'forgotten',
    ),
    'forgive' => array(
      0 => 'forgave',
      1 => 'forgiven',
    ),
    'forsake' => array(
      0 => 'forsook',
      1 => 'forsaken',
    ),
    'freeze' => array(
      0 => 'froze',
      1 => 'frozen',
    ),
    'frostbite' => array(
      0 => 'frostbit',
      1 => 'frostbitten',
    ),
    'get' => array(
      0 => 'got',
      1 => 'gotten',
    ),
    'give' => array(
      0 => 'gave',
      1 => 'given',
    ),
    'go' => array(
      0 => 'went',
      1 => 'gone',
    ),
    'grind' => array(
      0 => 'ground',
      1 => 'ground',
    ),
    'grow' => array(
      0 => 'grew',
      1 => 'grown',
    ),
    'hand-feed' => array(
      0 => 'hand-fed',
      1 => 'hand-fed',
    ),
    'handwrite' => array(
      0 => 'handwrote',
      1 => 'handwritten',
    ),
    'hang' => array(
      0 => 'hung',
      1 => 'hung',
    ),
    'have' => array(
      0 => 'had',
      1 => 'had',
    ),
    'hear' => array(
      0 => 'heard',
      1 => 'heard',
    ),
    'hew' => array(
      0 => 'hewed',
      1 => 'hewn',
    ),
    'hide' => array(
      0 => 'hid',
      1 => 'hidden',
    ),
    'hit' => array(
      0 => 'hit',
      1 => 'hit',
    ),
    'hold' => array(
      0 => 'held',
      1 => 'held',
    ),
    'hurt' => array(
      0 => 'hurt',
      1 => 'hurt',
    ),
    'inbreed' => array(
      0 => 'inbred',
      1 => 'inbred',
    ),
    'inlay' => array(
      0 => 'inlaid',
      1 => 'inlaid',
    ),
    'input' => array(
      0 => 'input',
      1 => 'input',
    ),
    'interbreed' => array(
      0 => 'interbred',
      1 => 'interbred',
    ),
    'interweave' => array(
      0 => 'interwove',
      1 => 'interwoven',
    ),
    'interwind' => array(
      0 => 'interwound',
      1 => 'interwound',
    ),
    'jerry-build' => array(
      0 => 'jerry-built',
      1 => 'jerry-built',
    ),
    'keep' => array(
      0 => 'kept',
      1 => 'kept',
    ),
    'kneel' => array(
      0 => 'knelt',
      1 => 'knelt',
    ),
    'knit' => array(
      0 => 'knitted',
      1 => 'knitted',
    ),
    'know' => array(
      0 => 'knew',
      1 => 'known',
    ),
    'lay' => array(
      0 => 'laid',
      1 => 'laid',
    ),
    'lead' => array(
      0 => 'led',
      1 => 'led',
    ),
    'lean' => array(
      0 => 'leaned',
      1 => 'leaned',
    ),
    'leap' => array(
      0 => 'leaped',
      1 => 'leaped',
    ),
    'learn' => array(
      0 => 'learned',
      1 => 'learned',
    ),
    'leave' => array(
      0 => 'left',
      1 => 'left',
    ),
    'lend' => array(
      0 => 'lent',
      1 => 'lent',
    ),
    'let' => array(
      0 => 'let',
      1 => 'let',
    ),
    'lie' => array(
      0 => 'lied',
      1 => 'lied',
    ),
    'light' => array(
      0 => 'lit',
      1 => 'lit',
    ),
    'lip-read' => array(
      0 => 'lip-read',
      1 => 'lip-read',
    ),
    'lose' => array(
      0 => 'lost',
      1 => 'lost',
    ),
    'make' => array(
      0 => 'made',
      1 => 'made',
    ),
    'mean' => array(
      0 => 'meant',
      1 => 'meant',
    ),
    'meet' => array(
      0 => 'met',
      1 => 'met',
    ),
    'miscast' => array(
      0 => 'miscast',
      1 => 'miscast',
    ),
    'misdeal' => array(
      0 => 'misdealt',
      1 => 'misdealt',
    ),
    'misdo' => array(
      0 => 'misdid',
      1 => 'misdone',
    ),
    'mishear' => array(
      0 => 'misheard',
      1 => 'misheard',
    ),
    'mislay' => array(
      0 => 'mislaid',
      1 => 'mislaid',
    ),
    'mislead' => array(
      0 => 'misled',
      1 => 'misled',
    ),
    'mislearn' => array(
      0 => 'mislearned',
      1 => 'mislearned',
    ),
    'misread' => array(
      0 => 'misread',
      1 => 'misread',
    ),
    'misset' => array(
      0 => 'misset',
      1 => 'misset',
    ),
    'misspeak' => array(
      0 => 'misspoke',
      1 => 'misspoken',
    ),
    'misspell' => array(
      0 => 'misspelled',
      1 => 'misspelled',
    ),
    'misspend' => array(
      0 => 'misspent',
      1 => 'misspent',
    ),
    'mistake' => array(
      0 => 'mistook',
      1 => 'mistaken',
    ),
    'misteach' => array(
      0 => 'mistaught',
      1 => 'mistaught',
    ),
    'misunderstand' => array(
      0 => 'misunderstood',
      1 => 'misunderstood',
    ),
    'miswrite' => array(
      0 => 'miswrote',
      1 => 'miswritten',
    ),
    'mow' => array(
      0 => 'mowed',
      1 => 'mowed',
    ),
    'offset' => array(
      0 => 'offset',
      1 => 'offset',
    ),
    'outbid' => array(
      0 => 'outbid',
      1 => 'outbid',
    ),
    'outbreed' => array(
      0 => 'outbred',
      1 => 'outbred',
    ),
    'outdo' => array(
      0 => 'outdid',
      1 => 'outdone',
    ),
    'outdraw' => array(
      0 => 'outdrew',
      1 => 'outdrawn',
    ),
    'outdrink' => array(
      0 => 'outdrank',
      1 => 'outdrunk',
    ),
    'outdrive' => array(
      0 => 'outdrove',
      1 => 'outdriven',
    ),
    'outfight' => array(
      0 => 'outfought',
      1 => 'outfought',
    ),
    'outfly' => array(
      0 => 'outflew',
      1 => 'outflown',
    ),
    'outgrow' => array(
      0 => 'outgrew',
      1 => 'outgrown',
    ),
    'outleap' => array(
      0 => 'outleaped',
      1 => 'outleaped',
    ),
    'outlie' => array(
      0 => 'outlied',
      1 => 'outlied',
    ),
    'outride' => array(
      0 => 'outrode',
      1 => 'outridden',
    ),
    'outrun' => array(
      0 => 'outran',
      1 => 'outrun',
    ),
    'outsell' => array(
      0 => 'outsold',
      1 => 'outsold',
    ),
    'outshine' => array(
      0 => 'outshined',
      1 => 'outshined',
    ),
    'outshoot' => array(
      0 => 'outshot',
      1 => 'outshot',
    ),
    'outsing' => array(
      0 => 'outsang',
      1 => 'outsung',
    ),
    'outsit' => array(
      0 => 'outsat',
      1 => 'outsat',
    ),
    'outsleep' => array(
      0 => 'outslept',
      1 => 'outslept',
    ),
    'outsmell' => array(
      0 => 'outsmelled',
      1 => 'outsmelled',
    ),
    'outspeak' => array(
      0 => 'outspoke',
      1 => 'outspoken',
    ),
    'outspeed' => array(
      0 => 'outsped',
      1 => 'outsped',
    ),
    'outspend' => array(
      0 => 'outspent',
      1 => 'outspent',
    ),
    'outswear' => array(
      0 => 'outswore',
      1 => 'outsworn',
    ),
    'outswim' => array(
      0 => 'outswam',
      1 => 'outswum',
    ),
    'outthink' => array(
      0 => 'outthought',
      1 => 'outthought',
    ),
    'outthrow' => array(
      0 => 'outthrew',
      1 => 'outthrown',
    ),
    'outwrite' => array(
      0 => 'outwrote',
      1 => 'outwritten',
    ),
    'overbid' => array(
      0 => 'overbid',
      1 => 'overbid',
    ),
    'overbreed' => array(
      0 => 'overbred',
      1 => 'overbred',
    ),
    'overbuild' => array(
      0 => 'overbuilt',
      1 => 'overbuilt',
    ),
    'overbuy' => array(
      0 => 'overbought',
      1 => 'overbought',
    ),
    'overcome' => array(
      0 => 'overcame',
      1 => 'overcome',
    ),
    'overdo' => array(
      0 => 'overdid',
      1 => 'overdone',
    ),
    'overdraw' => array(
      0 => 'overdrew',
      1 => 'overdrawn',
    ),
    'overdrink' => array(
      0 => 'overdrank',
      1 => 'overdrunk',
    ),
    'overeat' => array(
      0 => 'overate',
      1 => 'overeaten',
    ),
    'overfeed' => array(
      0 => 'overfed',
      1 => 'overfed',
    ),
    'overhang' => array(
      0 => 'overhung',
      1 => 'overhung',
    ),
    'overhear' => array(
      0 => 'overheard',
      1 => 'overheard',
    ),
    'overlay' => array(
      0 => 'overlaid',
      1 => 'overlaid',
    ),
    'overpay' => array(
      0 => 'overpaid',
      1 => 'overpaid',
    ),
    'override' => array(
      0 => 'overrode',
      1 => 'overridden',
    ),
    'overrun' => array(
      0 => 'overran',
      1 => 'overrun',
    ),
    'oversee' => array(
      0 => 'oversaw',
      1 => 'overseen',
    ),
    'oversell' => array(
      0 => 'oversold',
      1 => 'oversold',
    ),
    'oversew' => array(
      0 => 'oversewed',
      1 => 'oversewn',
    ),
    'overshoot' => array(
      0 => 'overshot',
      1 => 'overshot',
    ),
    'oversleep' => array(
      0 => 'overslept',
      1 => 'overslept',
    ),
    'overspeak' => array(
      0 => 'overspoke',
      1 => 'overspoken',
    ),
    'overspend' => array(
      0 => 'overspent',
      1 => 'overspent',
    ),
    'overspill' => array(
      0 => 'overspilled',
      1 => 'overspilled',
    ),
    'overtake' => array(
      0 => 'overtook',
      1 => 'overtaken',
    ),
    'overthink' => array(
      0 => 'overthought',
      1 => 'overthought',
    ),
    'overthrow' => array(
      0 => 'overthrew',
      1 => 'overthrown',
    ),
    'overwind' => array(
      0 => 'overwound',
      1 => 'overwound',
    ),
    'overwrite' => array(
      0 => 'overwrote',
      1 => 'overwritten',
    ),
    'partake' => array(
      0 => 'partook',
      1 => 'partaken',
    ),
    'pay' => array(
      0 => 'paid',
      1 => 'paid',
    ),
    'plead' => array(
      0 => 'pleaded',
      1 => 'pleaded',
    ),
    'prebuild' => array(
      0 => 'prebuilt',
      1 => 'prebuilt',
    ),
    'predo' => array(
      0 => 'predid',
      1 => 'predone',
    ),
    'premake' => array(
      0 => 'premade',
      1 => 'premade',
    ),
    'prepay' => array(
      0 => 'prepaid',
      1 => 'prepaid',
    ),
    'presell' => array(
      0 => 'presold',
      1 => 'presold',
    ),
    'preset' => array(
      0 => 'preset',
      1 => 'preset',
    ),
    'preshrink' => array(
      0 => 'preshrank',
      1 => 'preshrunk',
    ),
    'proofread' => array(
      0 => 'proofread',
      1 => 'proofread',
    ),
    'prove' => array(
      0 => 'proved',
      1 => 'proven',
    ),
    'put' => array(
      0 => 'put',
      1 => 'put',
    ),
    'quick-freeze' => array(
      0 => 'quick-froze',
      1 => 'quick-frozen',
    ),
    'quit' => array(
      0 => 'quit',
      1 => 'quit',
    ),
    'read' => array(
      0 => 'read (sounds like "red")',
      1 => 'read (sounds like "red")',
    ),
    'reawake' => array(
      0 => 'reawoke',
      1 => 'reawaken',
    ),
    'rebid' => array(
      0 => 'rebid',
      1 => 'rebid',
    ),
    'rebind' => array(
      0 => 'rebound',
      1 => 'rebound',
    ),
    'rebroadcast' => array(
      0 => 'rebroadcast',
      1 => 'rebroadcast',
    ),
    'rebuild' => array(
      0 => 'rebuilt',
      1 => 'rebuilt',
    ),
    'recast' => array(
      0 => 'recast',
      1 => 'recast',
    ),
    'recut' => array(
      0 => 'recut',
      1 => 'recut',
    ),
    'redeal' => array(
      0 => 'redealt',
      1 => 'redealt',
    ),
    'redo' => array(
      0 => 'redid',
      1 => 'redone',
    ),
    'redraw' => array(
      0 => 'redrew',
      1 => 'redrawn',
    ),
    'refit' => array(
      0 => 'refitted',
      1 => 'refitted',
    ),
    'regrind' => array(
      0 => 'reground',
      1 => 'reground',
    ),
    'regrow' => array(
      0 => 'regrew',
      1 => 'regrown',
    ),
    'rehang' => array(
      0 => 'rehung',
      1 => 'rehung',
    ),
    'rehear' => array(
      0 => 'reheard',
      1 => 'reheard',
    ),
    'reknit' => array(
      0 => 'reknitted',
      1 => 'reknitted',
    ),
    'relay' => array(
      0 => 'relayed',
      1 => 'relayed',
    ),
    'relearn' => array(
      0 => 'relearned',
      1 => 'relearned',
    ),
    'relight' => array(
      0 => 'relit',
      1 => 'relit',
    ),
    'remake' => array(
      0 => 'remade',
      1 => 'remade',
    ),
    'repay' => array(
      0 => 'repaid',
      1 => 'repaid',
    ),
    'reread' => array(
      0 => 'reread',
      1 => 'reread',
    ),
    'rerun' => array(
      0 => 'reran',
      1 => 'rerun',
    ),
    'resell' => array(
      0 => 'resold',
      1 => 'resold',
    ),
    'resend' => array(
      0 => 'resent',
      1 => 'resent',
    ),
    'reset' => array(
      0 => 'reset',
      1 => 'reset',
    ),
    'resew' => array(
      0 => 'resewed',
      1 => 'resewn',
    ),
    'retake' => array(
      0 => 'retook',
      1 => 'retaken',
    ),
    'reteach' => array(
      0 => 'retaught',
      1 => 'retaught',
    ),
    'retear' => array(
      0 => 'retore',
      1 => 'retorn',
    ),
    'retell' => array(
      0 => 'retold',
      1 => 'retold',
    ),
    'rethink' => array(
      0 => 'rethought',
      1 => 'rethought',
    ),
    'retread' => array(
      0 => 'retread',
      1 => 'retread',
    ),
    'retrofit' => array(
      0 => 'retrofitted',
      1 => 'retrofitted',
    ),
    'rewake' => array(
      0 => 'rewoke',
      1 => 'rewaken',
    ),
    'rewear' => array(
      0 => 'rewore',
      1 => 'reworn',
    ),
    'reweave' => array(
      0 => 'rewove',
      1 => 'rewoven',
    ),
    'rewed' => array(
      0 => 'rewed',
      1 => 'rewed',
    ),
    'rewet' => array(
      0 => 'rewet',
      1 => 'rewet',
    ),
    'rewin' => array(
      0 => 'rewon',
      1 => 'rewon',
    ),
    'rewind' => array(
      0 => 'rewound',
      1 => 'rewound',
    ),
    'rewrite' => array(
      0 => 'rewrote',
      1 => 'rewritten',
    ),
    'rid' => array(
      0 => 'rid',
      1 => 'rid',
    ),
    'ride' => array(
      0 => 'rode',
      1 => 'ridden',
    ),
    'ring' => array(
      0 => 'rang',
      1 => 'rung',
    ),
    'rise' => array(
      0 => 'rose',
      1 => 'risen',
    ),
    'roughcast' => array(
      0 => 'roughcast',
      1 => 'roughcast',
    ),
    'run' => array(
      0 => 'ran',
      1 => 'run',
    ),
    'sand-cast' => array(
      0 => 'sand-cast',
      1 => 'sand-cast',
    ),
    'saw' => array(
      0 => 'sawed',
      1 => 'sawed',
    ),
    'say' => array(
      0 => 'said',
      1 => 'said',
    ),
    'see' => array(
      0 => 'saw',
      1 => 'seen',
    ),
    'seek' => array(
      0 => 'sought',
      1 => 'sought',
    ),
    'sell' => array(
      0 => 'sold',
      1 => 'sold',
    ),
    'send' => array(
      0 => 'sent',
      1 => 'sent',
    ),
    'set' => array(
      0 => 'set',
      1 => 'set',
    ),
    'sew' => array(
      0 => 'sewed',
      1 => 'sewn',
    ),
    'shake' => array(
      0 => 'shook',
      1 => 'shaken',
    ),
    'shave' => array(
      0 => 'shaved',
      1 => 'shaved',
    ),
    'shear' => array(
      0 => 'sheared',
      1 => 'sheared',
    ),
    'shed' => array(
      0 => 'shed',
      1 => 'shed',
    ),
    'shine' => array(
      0 => 'shined',
      1 => 'shined',
    ),
    'shit' => array(
      0 => 'shit',
      1 => 'shit',
    ),
    'shoot' => array(
      0 => 'shot',
      1 => 'shot',
    ),
    'show' => array(
      0 => 'showed',
      1 => 'shown',
    ),
    'shrink' => array(
      0 => 'shrank',
      1 => 'shrunk',
    ),
    'shut' => array(
      0 => 'shut',
      1 => 'shut',
    ),
    'sight-read' => array(
      0 => 'sight-read',
      1 => 'sight-read',
    ),
    'sing' => array(
      0 => 'sang',
      1 => 'sung',
    ),
    'sink' => array(
      0 => 'sank',
      1 => 'sunk',
    ),
    'sit' => array(
      0 => 'sat',
      1 => 'sat',
    ),
    'slay' => array(
      0 => 'slayed',
      1 => 'slayed',
    ),
    'sleep' => array(
      0 => 'slept',
      1 => 'slept',
    ),
    'slide' => array(
      0 => 'slid',
      1 => 'slid',
    ),
    'sling' => array(
      0 => 'slung',
      1 => 'slung',
    ),
    'slink' => array(
      0 => 'slinked',
      1 => 'slinked',
    ),
    'slit' => array(
      0 => 'slit',
      1 => 'slit',
    ),
    'smell' => array(
      0 => 'smelled',
      1 => 'smelled',
    ),
    'sneak' => array(
      0 => 'sneaked',
      1 => 'sneaked',
    ),
    'sow' => array(
      0 => 'sowed',
      1 => 'sown',
    ),
    'speak' => array(
      0 => 'spoke',
      1 => 'spoken',
    ),
    'speed' => array(
      0 => 'sped',
      1 => 'sped',
    ),
    'spell' => array(
      0 => 'spelled',
      1 => 'spelled',
    ),
    'spend' => array(
      0 => 'spent',
      1 => 'spent',
    ),
    'spill' => array(
      0 => 'spilled',
      1 => 'spilled',
    ),
    'spin' => array(
      0 => 'spun',
      1 => 'spun',
    ),
    'spit' => array(
      0 => 'spit',
      1 => 'spit',
    ),
    'split' => array(
      0 => 'split',
      1 => 'split',
    ),
    'spoil' => array(
      0 => 'spoiled',
      1 => 'spoiled',
    ),
    'spoon-feed' => array(
      0 => 'spoon-fed',
      1 => 'spoon-fed',
    ),
    'spread' => array(
      0 => 'spread',
      1 => 'spread',
    ),
    'spring' => array(
      0 => 'sprang',
      1 => 'sprung',
    ),
    'stand ' => array(
      0 => 'stood',
      1 => 'stood',
    ),
    'steal' => array(
      0 => 'stole',
      1 => 'stolen',
    ),
    'stick' => array(
      0 => 'stuck',
      1 => 'stuck',
    ),
    'sting' => array(
      0 => 'stung',
      1 => 'stung',
    ),
    'stink' => array(
      0 => 'stunk',
      1 => 'stunk',
    ),
    'strew' => array(
      0 => 'strewed',
      1 => 'strewn',
    ),
    'stride' => array(
      0 => 'strode',
      1 => 'stridden',
    ),
    'strike' => array(
      0 => 'struck',
      1 => 'struck',
    ),
    'string' => array(
      0 => 'strung',
      1 => 'strung',
    ),
    'strive' => array(
      0 => 'strove',
      1 => 'striven',
    ),
    'sublet' => array(
      0 => 'sublet',
      1 => 'sublet',
    ),
    'sunburn' => array(
      0 => 'sunburned',
      1 => 'sunburned',
    ),
    'swear' => array(
      0 => 'swore',
      1 => 'sworn',
    ),
    'sweat' => array(
      0 => 'sweat',
      1 => 'sweat',
    ),
    'sweep' => array(
      0 => 'swept',
      1 => 'swept',
    ),
    'swell' => array(
      0 => 'swelled',
      1 => 'swollen',
    ),
    'swim' => array(
      0 => 'swam',
      1 => 'swum',
    ),
    'swing' => array(
      0 => 'swung',
      1 => 'swung',
    ),
    'take' => array(
      0 => 'took',
      1 => 'taken',
    ),
    'teach' => array(
      0 => 'taught',
      1 => 'taught',
    ),
    'tear' => array(
      0 => 'tore',
      1 => 'torn',
    ),
    'telecast' => array(
      0 => 'telecast',
      1 => 'telecast',
    ),
    'tell' => array(
      0 => 'told',
      1 => 'told',
    ),
    'test-drive' => array(
      0 => 'test-drove',
      1 => 'test-driven',
    ),
    'test-fly' => array(
      0 => 'test-flew',
      1 => 'test-flown',
    ),
    'think' => array(
      0 => 'thought',
      1 => 'thought',
    ),
    'throw' => array(
      0 => 'threw',
      1 => 'thrown',
    ),
    'thrust' => array(
      0 => 'thrust',
      1 => 'thrust',
    ),
    'tread' => array(
      0 => 'trod',
      1 => 'trodden',
    ),
    'typecast' => array(
      0 => 'typecast',
      1 => 'typecast',
    ),
    'typeset' => array(
      0 => 'typeset',
      1 => 'typeset',
    ),
    'typewrite' => array(
      0 => 'typewrote',
      1 => 'typewritten',
    ),
    'unbind' => array(
      0 => 'unbound',
      1 => 'unbound',
    ),
    'unclothe' => array(
      0 => 'unclothed',
      1 => 'unclothed',
    ),
    'underbid' => array(
      0 => 'underbid',
      1 => 'underbid',
    ),
    'undercut' => array(
      0 => 'undercut',
      1 => 'undercut',
    ),
    'underfeed' => array(
      0 => 'underfed',
      1 => 'underfed',
    ),
    'undergo' => array(
      0 => 'underwent',
      1 => 'undergone',
    ),
    'underlie' => array(
      0 => 'underlay',
      1 => 'underlain',
    ),
    'undersell' => array(
      0 => 'undersold',
      1 => 'undersold',
    ),
    'underspend' => array(
      0 => 'underspent',
      1 => 'underspent',
    ),
    'understand' => array(
      0 => 'understood',
      1 => 'understood',
    ),
    'undertake' => array(
      0 => 'undertook',
      1 => 'undertaken',
    ),
    'underwrite' => array(
      0 => 'underwrote',
      1 => 'underwritten',
    ),
    'undo' => array(
      0 => 'undid',
      1 => 'undone',
    ),
    'unfreeze' => array(
      0 => 'unfroze',
      1 => 'unfrozen',
    ),
    'unhang' => array(
      0 => 'unhung',
      1 => 'unhung',
    ),
    'unhide' => array(
      0 => 'unhid',
      1 => 'unhidden',
    ),
    'unknit' => array(
      0 => 'unknitted',
      1 => 'unknitted',
    ),
    'unlearn' => array(
      0 => 'unlearned',
      1 => 'unlearned',
    ),
    'unsew' => array(
      0 => 'unsewed',
      1 => 'unsewn',
    ),
    'unsling' => array(
      0 => 'unslung',
      1 => 'unslung',
    ),
    'unspin' => array(
      0 => 'unspun',
      1 => 'unspun',
    ),
    'unstick' => array(
      0 => 'unstuck',
      1 => 'unstuck',
    ),
    'unstring' => array(
      0 => 'unstrung',
      1 => 'unstrung',
    ),
    'unweave' => array(
      0 => 'unwove',
      1 => 'unwoven',
    ),
    'unwind' => array(
      0 => 'unwound',
      1 => 'unwound',
    ),
    'uphold' => array(
      0 => 'upheld',
      1 => 'upheld',
    ),
    'upset' => array(
      0 => 'upset',
      1 => 'upset',
    ),
    'wake' => array(
      0 => 'woke',
      1 => 'woken',
    ),
    'waylay' => array(
      0 => 'waylaid',
      1 => 'waylaid',
    ),
    'wear' => array(
      0 => 'wore',
      1 => 'worn',
    ),
    'weave' => array(
      0 => 'wove',
      1 => 'woven',
    ),
    'wed' => array(
      0 => 'wed',
      1 => 'wed',
    ),
    'weep' => array(
      0 => 'wept',
      1 => 'wept',
    ),
    'wet' => array(
      0 => 'wet',
      1 => 'wet',
    ),
    'whet' => array(
      0 => 'whetted',
      1 => 'whetted',
    ),
    'win' => array(
      0 => 'won',
      1 => 'won',
    ),
    'wind' => array(
      0 => 'wound',
      1 => 'wound',
    ),
    'withdraw' => array(
      0 => 'withdrew',
      1 => 'withdrawn',
    ),
    'withhold' => array(
      0 => 'withheld',
      1 => 'withheld',
    ),
    'withstand' => array(
      0 => 'withstood',
      1 => 'withstood',
    ),
    'wring' => array(
      0 => 'wrung',
      1 => 'wrung',
    ),
    'write' => array(
      0 => 'wrote',
      1 => 'written',
    ),
  );

  /**
   * Default map of accented and special characters to ASCII characters
   *
   * @var array
   */
  protected static $_transliteration = array(
    '/ä|æ|ǽ/' => 'ae',
    '/ö|œ/' => 'oe',
    '/ü/' => 'ue',
    '/Ä/' => 'Ae',
    '/Ü/' => 'Ue',
    '/Ö/' => 'Oe',
    '/À|Á|Â|Ã|Ä|Å|Ǻ|Ā|Ă|Ą|Ǎ/' => 'A',
    '/à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª/' => 'a',
    '/Ç|Ć|Ĉ|Ċ|Č/' => 'C',
    '/ç|ć|ĉ|ċ|č/' => 'c',
    '/Ð|Ď|Đ/' => 'D',
    '/ð|ď|đ/' => 'd',
    '/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě/' => 'E',
    '/è|é|ê|ë|ē|ĕ|ė|ę|ě/' => 'e',
    '/Ĝ|Ğ|Ġ|Ģ/' => 'G',
    '/ĝ|ğ|ġ|ģ/' => 'g',
    '/Ĥ|Ħ/' => 'H',
    '/ĥ|ħ/' => 'h',
    '/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ/' => 'I',
    '/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı/' => 'i',
    '/Ĵ/' => 'J',
    '/ĵ/' => 'j',
    '/Ķ/' => 'K',
    '/ķ/' => 'k',
    '/Ĺ|Ļ|Ľ|Ŀ|Ł/' => 'L',
    '/ĺ|ļ|ľ|ŀ|ł/' => 'l',
    '/Ñ|Ń|Ņ|Ň/' => 'N',
    '/ñ|ń|ņ|ň|ŉ/' => 'n',
    '/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ/' => 'O',
    '/ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º/' => 'o',
    '/Ŕ|Ŗ|Ř/' => 'R',
    '/ŕ|ŗ|ř/' => 'r',
    '/Ś|Ŝ|Ş|Š/' => 'S',
    '/ś|ŝ|ş|š|ſ/' => 's',
    '/Ţ|Ť|Ŧ/' => 'T',
    '/ţ|ť|ŧ/' => 't',
    '/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ/' => 'U',
    '/ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ/' => 'u',
    '/Ý|Ÿ|Ŷ/' => 'Y',
    '/ý|ÿ|ŷ/' => 'y',
    '/Ŵ/' => 'W',
    '/ŵ/' => 'w',
    '/Ź|Ż|Ž/' => 'Z',
    '/ź|ż|ž/' => 'z',
    '/Æ|Ǽ/' => 'AE',
    '/ß/' => 'ss',
    '/Ĳ/' => 'IJ',
    '/ĳ/' => 'ij',
    '/Œ/' => 'OE',
    '/ƒ/' => 'f'
  );

  /**
   * Method cache array.
   *
   * @var array
   */
  protected static $_cache = array();

  /**
   * The initial state of Inflector so reset() works.
   *
   * @var array
   */
  protected static $_initialState = array();

  /**
   * Cache inflected values, and return if already available
   *
   * @param string $type Inflection type
   * @param string $key Original value
   * @param string $value Inflected value
   * @return string Inflected value, from cache
   */
  protected static function _cache($type, $key, $value = false)
  {
    $key = '_' . $key;
    $type = '_' . $type;
    if ($value !== false) {
      self::$_cache[$type][$key] = $value;
      return $value;
    }
    if (!isset(self::$_cache[$type][$key])) {
      return false;
    }
    return self::$_cache[$type][$key];
  }

  /**
   * Clears Inflectors inflected value caches. And resets the inflection
   * rules to the initial values.
   *
   * @return void
   */
  public static function reset()
  {
    if (empty(self::$_initialState)) {
      self::$_initialState = get_class_vars(__CLASS__);
      return;
    }
    foreach (self::$_initialState as $key => $val) {
      if ($key != '_initialState') {
        self::${$key} = $val;
      }
    }
  }

  /**
   * Adds custom inflection $rules, of either 'plural', 'singular' or 'transliteration' $type.
   *
   * ### Usage:
   *
   * {{{
   * Inflector::rules('plural', array('/^(inflect)or$/i' => '\1ables'));
   * Inflector::rules('plural', array(
   *     'rules' => array('/^(inflect)ors$/i' => '\1ables'),
   *     'uninflected' => array('dontinflectme'),
   *     'irregular' => array('red' => 'redlings')
   * ));
   * Inflector::rules('transliteration', array('/å/' => 'aa'));
   * }}}
   *
   * @param string $type The type of inflection, either 'plural', 'singular' or 'transliteration'
   * @param array $rules Array of rules to be added.
   * @param boolean $reset If true, will unset default inflections for all
   *        new rules that are being defined in $rules.
   * @access public
   * @return void
   */
  public static function rules($type, $rules, $reset = false)
  {
    $var = '_' . $type;

    switch ($type) {
    case 'transliteration':
      if ($reset) {
        self::$_transliteration = $rules;
      }
      else {
        self::$_transliteration = $rules + self::$_transliteration;
      }
      break;

    default:
      foreach ($rules as $rule => $pattern) {
        if (is_array($pattern)) {
          if ($reset) {
            self::${$var}[$rule] = $pattern;
          }
          else {
            self::${$var}[$rule] = array_merge($pattern, self::${$var}[$rule]);
          }
          unset($rules[$rule], self::${$var}['cache' . ucfirst($rule)]);
          if (isset(self::${$var}['merged'][$rule])) {
            unset(self::${$var}['merged'][$rule]);
          }
          if ($type === 'plural') {
            self::$_cache['pluralize'] = self::$_cache['tableize'] = array();
          }
          elseif ($type === 'singular') {
            self::$_cache['singularize'] = array();
          }
        }
      }
      self::${$var}['rules'] = array_merge($rules, self::${$var}['rules']);
      break;
    }
  }

  /**
   * Return $word in plural form.
   *
   * @param string $word Word in singular
   * @return string Word in plural
   * @access public
   * @link http://book.cakephp.org/view/1479/Class-methods
   */
  public static function pluralize($word)
  {
    if (isset(self::$_cache['pluralize'][$word])) {
      return self::$_cache['pluralize'][$word];
    }

    if (!isset(self::$_plural['merged']['irregular'])) {
      self::$_plural['merged']['irregular'] = self::$_plural['irregular'];
    }

    if (!isset(self::$_plural['merged']['uninflected'])) {
      self::$_plural['merged']['uninflected'] = array_merge(self::$_plural['uninflected'], self::$_uninflected);
    }

    if (!isset(self::$_plural['cacheUninflected']) || !isset(self::$_plural['cacheIrregular'])) {
      self::$_plural['cacheUninflected'] = '(?:' . implode('|', self::$_plural['merged']['uninflected']) . ')';
      self::$_plural['cacheIrregular'] = '(?:' . implode('|', array_keys(self::$_plural['merged']['irregular'])) . ')';
    }

    if (preg_match('/(.*)\\b(' . self::$_plural['cacheIrregular'] . ')$/i', $word, $regs)) {
      self::$_cache['pluralize'][$word] = $regs[1] . substr($word, 0, 1)
        . substr(self::$_plural['merged']['irregular'][strtolower($regs[2])], 1);
      return self::$_cache['pluralize'][$word];
    }

    if (preg_match('/^(' . self::$_plural['cacheUninflected'] . ')$/i', $word, $regs)) {
      self::$_cache['pluralize'][$word] = $word;
      return $word;
    }

    foreach (self::$_plural['rules'] as $rule => $replacement) {
      if (preg_match($rule, $word)) {
        self::$_cache['pluralize'][$word] = preg_replace($rule, $replacement, $word);
        return self::$_cache['pluralize'][$word];
      }
    }
  }

  /**
   * Return $word in singular form.
   *
   * @param string $word Word in plural
   * @return string Word in singular
   * @access public
   * @link http://book.cakephp.org/view/1479/Class-methods
   */
  public static function singularize($word)
  {
    if (isset(self::$_cache['singularize'][$word])) {
      return self::$_cache['singularize'][$word];
    }

    if (!isset(self::$_singular['merged']['uninflected'])) {
      self::$_singular['merged']['uninflected'] = array_merge(self::$_singular['uninflected'], self::$_uninflected);
    }

    if (!isset(self::$_singular['merged']['irregular'])) {
      self::$_singular['merged']['irregular'] = array_merge(self::$_singular['irregular'], array_flip(self::$_plural['irregular']));
    }

    if (!isset(self::$_singular['cacheUninflected']) || !isset(self::$_singular['cacheIrregular'])) {
      self::$_singular['cacheUninflected'] = '(?:' . join('|', self::$_singular['merged']['uninflected']) . ')';
      self::$_singular['cacheIrregular'] = '(?:' . join('|', array_keys(self::$_singular['merged']['irregular'])) . ')';
    }

    if (preg_match('/(.*)\\b(' . self::$_singular['cacheIrregular'] . ')$/i', $word, $regs)) {
      self::$_cache['singularize'][$word] = $regs[1] . substr($word, 0, 1)
        . substr(self::$_singular['merged']['irregular'][strtolower($regs[2])], 1);
      return self::$_cache['singularize'][$word];
    }

    if (preg_match('/^(' . self::$_singular['cacheUninflected'] . ')$/i', $word, $regs)) {
      self::$_cache['singularize'][$word] = $word;
      return $word;
    }

    foreach (self::$_singular['rules'] as $rule => $replacement) {
      if (preg_match($rule, $word)) {
        self::$_cache['singularize'][$word] = preg_replace($rule, $replacement, $word);
        return self::$_cache['singularize'][$word];
      }
    }
    self::$_cache['singularize'][$word] = $word;
    return $word;
  }

  public static function pastSimple($present)
  {
    if (!($result = self::_cache(__FUNCTION__, $present))) {
      $result = array_key_exists($present, self::$_irregularVerbs) ? self::$_irregularVerbs[$present][0]
        : substr($present, -1) === 'e' ? $present . 'd' : $present . 'ed';
    }
    return $result;
  }

  public static function pastParticiple($present)
  {
    if (!($result = self::_cache(__FUNCTION__, $present))) {
      $result = array_key_exists($present, self::$_irregularVerbs) ? self::$_irregularVerbs[$present][1]
        : substr($present, -1) === 'e' ? $present . 'd' : $present . 'ed';
    }
    return $result;
  }

  /**
   * Returns the given lower_case_and_underscored_word as a CamelCased word.
   *
   * @param string $lower_case_and_underscored_word Word to camelize
   * @return string Camelized word. LikeThis.
   * @access public
   * @link http://book.cakephp.org/view/1479/Class-methods
   */
  public static function camelize($lowerCaseAndUnderscoredWord)
  {
    if (!($result = self::_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord))) {
      $result = str_replace(' ', '', Inflector::humanize($lowerCaseAndUnderscoredWord));
      self::_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord, $result);
    }
    return $result;
  }

  /**
   * Returns the given camelCasedWord as an underscored_word.
   *
   * @param string $camelCasedWord Camel-cased word to be "underscorized"
   * @return string Underscore-syntaxed version of the $camelCasedWord
   * @access public
   * @link http://book.cakephp.org/view/1479/Class-methods
   */
  public static function underscore($camelCasedWord)
  {
    if (!($result = self::_cache(__FUNCTION__, $camelCasedWord))) {
      $result = ctype_upper($camelCasedWord) ? strtolower($camelCasedWord)
        : strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camelCasedWord));
      self::_cache(__FUNCTION__, $camelCasedWord, $result);
    }
    return $result;
  }

  /**
   * Returns the given underscored_word_group as a Human Readable Word Group.
   * (Underscores are replaced by spaces and capitalized following words.)
   *
   * @param string $lower_case_and_underscored_word String to be made more readable
   * @return string Human-readable string
   * @access public
   * @link http://book.cakephp.org/view/1479/Class-methods
   */
  public static function humanize($lowerCaseAndUnderscoredWord)
  {
    if (!($result = self::_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord))) {
      $result = ucwords(str_replace('_', ' ', $lowerCaseAndUnderscoredWord));
      self::_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord, $result);
    }
    return $result;
  }

  /**
   * Returns corresponding table name for given model $className. ("people" for the model class "Person").
   *
   * @param string $className Name of class to get database table name for
   * @return string Name of the database table for given class
   * @access public
   * @link http://book.cakephp.org/view/1479/Class-methods
   */
  public static function tableize($className)
  {
    if (!($result = self::_cache(__FUNCTION__, $className))) {
      /*$parts = explode(NS, $className);
      $_className = array_pop($parts);
      $_result = Inflector::pluralize(Inflector::underscore($_className));
      $parts = array_map('strtolower', $parts);
      $parts[] = $_result;
      $result = implode('_', $parts);*/
      $result = Inflector::pluralize(Inflector::underscore($className));
      self::_cache(__FUNCTION__, $className, $result);
    }
    return $result;
  }

  /**
   * Returns Cake model class name ("Person" for the database table "people".) for given database table.
   *
   * @param string $tableName Name of database table to get class name for
   * @return string Class name
   * @access public
   * @link http://book.cakephp.org/view/1479/Class-methods
   */
  public static function classify($tableName)
  {
    if (!($result = self::_cache(__FUNCTION__, $tableName))) {
      //$parts = explode('_', $tableName);
      //$_tableName = array_pop($parts);
      //$_result = Inflector::camelize(Inflector::singularize($_tableName));
      //$parts = array_map('ucfirst', $parts);
      //$parts[] = $_result;
      //$result = implode(NS, $parts);
      $result = Inflector::camelize(Inflector::singularize($tableName));
      self::_cache(__FUNCTION__, $tableName, $result);
    }
    return $result;
  }

  /**
   * Returns camelBacked version of an underscored string.
   *
   * @param string $string
   * @return string in variable form
   * @access public
   * @link http://book.cakephp.org/view/1479/Class-methods
   */
  public static function variable($string)
  {
    if (!($result = self::_cache(__FUNCTION__, $string))) {
      $string2 = Inflector::camelize(Inflector::underscore($string));
      $replace = strtolower(substr($string2, 0, 1));
      $result = preg_replace('/\\w/', $replace, $string2, 1);
      self::_cache(__FUNCTION__, $string, $result);
    }
    return $result;
  }

  /**
   * Returns a string with all spaces converted to underscores (by default), accented
   * characters converted to non-accented characters, and non word characters removed.
   *
   * @param string $string the string you want to slug
   * @param string $replacement will replace keys in map
   * @param array $map extra elements to map to the replacement
   * @return string
   * @access public
   * @link http://book.cakephp.org/view/1479/Class-methods
   */
  public static function slug($string, $replacement = '-')
  {
    $quotedReplacement = preg_quote($replacement, '/');

    $merge = array(
      '/[^\s\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]/m' => ' ',
      '/\\s+/' => $replacement,
      sprintf('/^[%s]+|[%s]+$/', $quotedReplacement, $quotedReplacement) => '',
    );

    $map = self::$_transliteration + $merge;
    return preg_replace(array_keys($map), array_values($map), strtolower($string));
  }
}

// Store the initial state
Inflector::reset();
